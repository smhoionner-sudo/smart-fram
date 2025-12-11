<?php
// delete_event.php
header('Content-Type: application/json');
session_start();
require_once 'db.php';

if (isset($_GET['id']) && isset($_SESSION['username'])) {
    $id = $_GET['id'];

    // Admin สามารถลบได้ทุก Event
    $sql = "DELETE FROM events WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ลบไม่สำเร็จ']);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
}
?>