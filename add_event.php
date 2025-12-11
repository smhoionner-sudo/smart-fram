<?php
// add_event.php
header('Content-Type: application/json');
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['username'])) {
    
    $title = $_POST['title'];
    $start = $_POST['start'];
    $end = !empty($_POST['end']) ? $_POST['end'] : null;
    $color = $_POST['color'];
    
    // Checkbox จะส่งมาถ้าถูกติ๊ก
    $all_day = isset($_POST['all_day']) ? 1 : 0;
    $is_global = isset($_POST['is_global']) ? 1 : 0;
    
    // ดึงชื่อผู้บันทึกจาก Session
    $recorder_name = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : $_SESSION['username'];

    $sql = "INSERT INTO events (title, start_event, end_event, color, all_day, is_global, recorder_name) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssiss", $title, $start, $end, $color, $all_day, $is_global, $recorder_name);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or Invalid Request']);
}
?>