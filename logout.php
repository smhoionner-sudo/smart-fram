<?php
session_start();

// 1. ล้างค่า Session ทั้งหมด
$_SESSION = [];

// 2. ทำลาย Session ทิ้ง
session_destroy();

// (ตัวเลือกเสริม) ถ้าต้องการให้ Logout แล้วลืมชื่อผู้ใช้ที่จดจำไว้ด้วย ให้เอา comment ด้านล่างออก
// setcookie('remember_username', '', time() - 3600, "/");

// 3. ส่งกลับไปหน้า Login
header("Location: login.php");
exit;
?>