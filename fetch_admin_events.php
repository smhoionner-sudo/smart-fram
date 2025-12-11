<?php
session_start();
include "./db.php";
header('Content-Type: application/json');

// ตรวจสอบว่าเป็น Admin เท่านั้น
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([]); 
    exit;
}

$events = [];

// 1. ดึงข้อมูลการเพาะปลูกของ "ทุกคน" (Agricultural Logs)
// JOIN กับตาราง users เพื่อเอาชื่อคนบันทึกมาแสดง
$sql_logs = "SELECT l.id, l.activity_name, l.activity_date, l.crop_type, l.crop_variety, u.name, u.surname 
             FROM agricultural_logs l
             JOIN users u ON l.user_id = u.id";
$result = $conn->query($sql_logs);

while($row = $result->fetch_assoc()) {
    // สีตามประเภทพืช
    $color = '#588157'; 
    if($row['crop_type'] == 'rice') $color = '#A3B18A';
    elseif($row['crop_type'] == 'longan') $color = '#D4A373';
    elseif($row['crop_type'] == 'rubber') $color = '#52796F';

    $events[] = [
        'id' => 'log_' . $row['id'], // ใส่ prefix เพื่อแยกประเภท
        'title' => "🌱 " . $row['name'] . ": " . $row['activity_name'], // โชว์ชื่อคนทำ
        'start' => $row['activity_date'],
        'color' => $color,
        'extendedProps' => [
            'type' => 'log',
            'detail' => 'พืช: ' . $row['crop_variety'] . ' | โดยคุณ: ' . $row['name'] . ' ' . $row['surname'],
            'db_id' => $row['id'],
            'can_edit' => false, // Log แก้ไขไม่ได้ (ให้ลบอย่างเดียวเพื่อความปลอดภัยข้อมูล)
            'can_delete' => true
        ]
    ];
}

// 2. ดึงกิจกรรมในปฏิทินทั้งหมด (Calendar Events)
$sql_cal = "SELECT e.*, u.name, u.surname FROM calendar_events e JOIN users u ON e.user_id = u.id";
$result2 = $conn->query($sql_cal);

while($row = $result2->fetch_assoc()) {
    $is_announce = $row['is_global'];
    $prefix = $is_announce ? '📢 [ประกาศ] ' : '👤 ';
    
    $events[] = [
        'id' => $row['id'], // ID ตรงๆ
        'title' => $prefix . $row['title'] . ' (' . $row['name'] . ')',
        'start' => $row['start_date'],
        'end' => $row['end_date'],
        'color' => $row['color'],
        'extendedProps' => [
            'type' => 'event',
            'description' => $row['description'],
            'is_global' => $is_announce,
            'owner' => $row['name'] . ' ' . $row['surname'],
            'can_edit' => true, // กิจกรรมปฏิทินแก้ไขได้
            'can_delete' => true
        ]
    ];
}

echo json_encode($events);
?>