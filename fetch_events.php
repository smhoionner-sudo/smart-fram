<?php
session_start();
include "./db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['userid'])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['userid'];
$events = [];

// 1. ดึงข้อมูลจาก Agricultural Logs (บันทึกเพาะปลูกของ user)
$sql_logs = "SELECT id, activity_name, activity_date, crop_type, crop_variety FROM agricultural_logs WHERE user_id = ?";
$stmt = $conn->prepare($sql_logs);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while($row = $result->fetch_assoc()) {
    // กำหนดสีตามพืช
    $color = '#588157'; // เขียว (ค่าเริ่มต้น)
    if($row['crop_type'] == 'rice') $color = '#A3B18A';
    elseif($row['crop_type'] == 'longan') $color = '#D4A373';
    elseif($row['crop_type'] == 'rubber') $color = '#52796F';

    $events[] = [
        'id' => 'log_' . $row['id'], // ใส่ prefix เพื่อแยกประเภท
        'title' => $row['activity_name'] . ' (' . $row['crop_variety'] . ')',
        'start' => $row['activity_date'],
        'color' => $color,
        'extendedProps' => [
            'type' => 'log',
            'detail' => 'บันทึกจากระบบเพาะปลูก',
            'can_delete' => false // log ลบหน้านี้ไม่ได้ ต้องไปลบหน้า history
        ]
    ];
}

// 2. ดึงข้อมูลจาก Calendar Events (กิจกรรมส่วนตัว + ประกาศ Admin)
// เงื่อนไข: เป็นของ user คนนี้ OR เป็น global (admin ประกาศ)
$sql_cal = "SELECT * FROM calendar_events WHERE user_id = ? OR is_global = 1";
$stmt2 = $conn->prepare($sql_cal);
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$result2 = $stmt2->get_result();

while($row = $result2->fetch_assoc()) {
    // เช็คสิทธิ์การลบ (ลบได้ถ้าเป็นของตัวเอง)
    $can_delete = ($row['user_id'] == $user_id);
    
    // ถ้าเป็น Global ให้สีแดง/ส้ม
    $color = $row['is_global'] ? '#E76F51' : $row['color'];

    $events[] = [
        'id' => $row['id'],
        'title' => ($row['is_global'] ? '[ประกาศ] ' : '') . $row['title'],
        'start' => $row['start_date'],
        'end' => $row['end_date'],
        'color' => $color,
        'extendedProps' => [
            'type' => 'event',
            'detail' => $row['description'],
            'can_delete' => $can_delete,
            'is_global' => $row['is_global']
        ]
    ];
}

echo json_encode($events);
?>