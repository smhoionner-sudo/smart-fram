<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project1"; // ★★★ กำหนดชื่อ Database เป็น project1 ★★★

// สร้างการเชื่อมต่อแบบ MySQLi (ตามโค้ด Login ของคุณที่ใช้ $conn->prepare)
$conn = new mysqli($servername, $username, $password, $dbname);

// เช็คการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ตั้งค่าภาษาไทย
$conn->set_charset("utf8");
?>