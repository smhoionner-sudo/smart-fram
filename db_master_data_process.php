<?php
require_once 'db.php';

// ตรวจสอบ Action (Add/Edit) จาก POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $type = $_POST['type']; // รับค่าหมวดหมู่มาด้วย
    $name = trim($_POST['name']);

    if ($action == 'add') {
        // เพิ่มข้อมูล
        $sql = "INSERT INTO master_data (type, name) VALUES (?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ss", $type, $name);
            if ($stmt->execute()) {
                // ส่งค่า active=$type กลับไป เพื่อให้เปิด Tab เดิม
                echo "<script>alert('เพิ่มข้อมูลสำเร็จ'); window.location='edit-production_admin.php?active=" . $type . "';</script>";
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    } elseif ($action == 'edit') {
        // แก้ไขข้อมูล
        $id = $_POST['id'];
        $sql = "UPDATE master_data SET name = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("si", $name, $id);
            if ($stmt->execute()) {
                // ส่งค่า active=$type กลับไป
                echo "<script>alert('อัปเดตข้อมูลสำเร็จ'); window.location='edit-production_admin.php?active=" . $type . "';</script>";
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// ตรวจสอบ Action (Delete) จาก GET
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // 1. หา type ของข้อมูลที่จะลบก่อน เพื่อจะได้ส่งกลับไปถูกหมวด
    $type = "";
    $sql_find = "SELECT type FROM master_data WHERE id = ?";
    if ($stmt_find = $conn->prepare($sql_find)) {
        $stmt_find->bind_param("i", $id);
        $stmt_find->execute();
        $stmt_find->bind_result($found_type);
        if ($stmt_find->fetch()) {
            $type = $found_type;
        }
        $stmt_find->close();
    }

    // 2. ทำการลบข้อมูล
    $sql = "DELETE FROM master_data WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            // ส่งค่า active=$type กลับไป
            echo "<script>alert('ลบข้อมูลเรียบร้อย'); window.location='edit-production_admin.php?active=" . $type . "';</script>";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>