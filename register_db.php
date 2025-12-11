<?php
session_start();
require_once 'db.php'; // เรียกไฟล์เชื่อมต่อฐานข้อมูล

if (isset($_POST['signup_btn'])) {
    // รับค่าจากฟอร์ม
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    // กำหนดค่าเริ่มต้น
    $status = 'active'; 
    $role = 'user';     

    // เข้ารหัส Password เพื่อความปลอดภัย
    $password_hashed = password_hash($password, PASSWORD_DEFAULT);

    try {
        // 1. เช็คว่า Username ซ้ำหรือไม่
        $check_stmt = $conn->prepare("SELECT username FROM users WHERE username = :username");
        $check_stmt->bindParam(':username', $username);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            // ถ้าซ้ำ ให้แจ้งเตือนและกลับไปหน้าเดิม
            echo "<script>
                alert('Username นี้มีผู้ใช้งานแล้ว กรุณาเปลี่ยนใหม่');
                window.location.href='register.php';
            </script>";
        } else {
            // 2. ถ้าไม่ซ้ำ ให้บันทึกข้อมูล
            $sql = "INSERT INTO users (username, password, name, surname, status, role) 
                    VALUES (:username, :password, :name, :surname, :status, :role)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $password_hashed);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':surname', $surname);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':role', $role);

            if ($stmt->execute()) {
                echo "<script>
                    alert('สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ');
                    window.location.href='login.php'; 
                </script>";
            } else {
                echo "<script>
                    alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                    window.location.href='register.php';
                </script>";
            }
        }
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>