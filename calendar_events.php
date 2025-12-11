<?php
// calendar_events.php
header('Content-Type: application/json');
session_start();
require_once 'db.php';

// ดึงกิจกรรมทั้งหมด (สำหรับ Admin)
$sql = "SELECT * FROM events";
$result = $conn->query($sql);

$events = array();

while ($row = $result->fetch_assoc()) {
    $events[] = array(
        'id' => $row['id'],
        'title' => $row['title'],
        'start' => $row['start_event'],
        'end' => $row['end_event'],
        'color' => $row['color'],
        'allDay' => (bool)$row['all_day'],
        // extendedProps สำหรับข้อมูลเพิ่มเติม
        'recorder_name' => $row['recorder_name'],
        'is_global' => (bool)$row['is_global'],
        // ถ้าเป็น Global events อาจจะล็อคไม่ให้ลบ (แล้วแต่ดีไซน์) ในที่นี้ Admin ลบได้หมด
        'locked' => false 
    );
}

echo json_encode($events);
?>