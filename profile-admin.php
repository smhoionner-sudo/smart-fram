<?php
session_start();
include "./db.php";

// 1. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['userid'];
$msg = "";

// --- ส่วนที่ 1: อัปเดตข้อมูลทั่วไป ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $surname = trim($_POST['surname']);
    $username = trim($_POST['username']);

    // เช็คว่า Username ซ้ำกับคนอื่นไหม
    $check_sql = "SELECT id FROM users WHERE username = ? AND id != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("si", $username, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $msg = "<div class='alert alert-warning rounded-pill shadow-sm mb-4 text-center'><i class='fas fa-exclamation-triangle me-2'></i>Username นี้มีผู้ใช้งานแล้ว</div>";
    } else {
        $update_sql = "UPDATE users SET name = ?, surname = ?, username = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssi", $name, $surname, $username, $user_id);
        
        if ($stmt->execute()) {
            // อัปเดต Session
            $_SESSION['fullname'] = $name . " " . $surname;
            $_SESSION['username'] = $username;
            $msg = "<div class='alert alert-success rounded-pill shadow-sm mb-4 text-center'><i class='fas fa-check-circle me-2'></i>บันทึกข้อมูลส่วนตัวสำเร็จ!</div>";
        } else {
            $msg = "<div class='alert alert-danger rounded-pill shadow-sm mb-4 text-center'>เกิดข้อผิดพลาดในการบันทึก</div>";
        }
    }
}

// --- ส่วนที่ 2: เปลี่ยนรหัสผ่าน ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    $sql_pass = "SELECT password FROM users WHERE id = ?";
    $stmt_pass = $conn->prepare($sql_pass);
    $stmt_pass->bind_param("i", $user_id);
    $stmt_pass->execute();
    $res_pass = $stmt_pass->get_result()->fetch_assoc();

    if (password_verify($current_pass, $res_pass['password'])) {
        if ($new_pass === $confirm_pass) {
            $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
            $update_pass_sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($update_pass_sql);
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $msg = "<div class='alert alert-success rounded-pill shadow-sm mb-4 text-center'><i class='fas fa-key me-2'></i>เปลี่ยนรหัสผ่านสำเร็จ!</div>";
            }
        } else {
            $msg = "<div class='alert alert-danger rounded-pill shadow-sm mb-4 text-center'>รหัสผ่านใหม่ไม่ตรงกัน</div>";
        }
    } else {
        $msg = "<div class='alert alert-danger rounded-pill shadow-sm mb-4 text-center'>รหัสผ่านปัจจุบันไม่ถูกต้อง</div>";
    }
}

// ดึงข้อมูลล่าสุดมาแสดง
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// สร้างตัวแปรสำหรับ Sidebar (ถ้าใน sidebar.php ใช้ตัวแปรนี้)
$admin_fullname = ($user['name'] ?? 'Admin') . ' ' . ($user['surname'] ?? '');
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขโปรไฟล์ผู้ดูแลระบบ</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-body: #f3f4f6; --card-bg: #ffffff; --text-primary: #1f2937; --text-secondary: #6b7280;
            --border-color: #e5e7eb; --agri-green: #588157; --agri-dark: #3a5a40;
            --sidebar-width: 260px; --sidebar-bg: #2b3035;
        }

        body { font-family: 'Kanit', sans-serif; background-color: var(--bg-body); color: var(--text-primary); padding-bottom: 50px; }

        /* --- Sidebar & Layout CSS (Standard) --- */
        .sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; top: 0; left: 0; background-color: var(--sidebar-bg); color: white; z-index: 1000; display: flex; flex-direction: column; padding: 20px; }
        .nav-link-custom { color: rgba(255,255,255,0.75); padding: 12px 15px; border-radius: 10px; text-decoration: none; display: flex; align-items: center; margin-bottom: 5px; transition: 0.3s; }
        .nav-link-custom:hover, .nav-link-custom.active { background-color: rgba(255,255,255,0.1); color: white; }
        .nav-link-custom i { width: 30px; text-align: center; }
        .user-profile-sidebar { margin-top: auto; background: rgba(0,0,0,0.3); padding: 15px; border-radius: 12px; display: flex; align-items: center; gap: 10px; }
        .user-avatar { width: 40px; height: 40px; background: white; color: var(--sidebar-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0; }

        .main-content { margin-left: var(--sidebar-width); padding: 30px; transition: 0.3s; }
        .mobile-nav { background: white; padding: 15px 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 999; }
        
        .offcanvas-custom { background-color: rgba(43, 48, 53, 0.95) !important; backdrop-filter: blur(10px); color: white; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px; height: auto !important; min-height: 50vh; }
        .offcanvas-custom .btn-close { filter: invert(1); }

        @media (max-width: 991.98px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 20px; }
        }

        /* --- Page Specific Styles --- */
        .profile-card {
            background: white; border-radius: 20px; border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.03); overflow: hidden; height: auto;
        }
        .profile-header {
            background: linear-gradient(135deg, #2b3035 0%, #4b5563 100%); /* Match sidebar theme */
            padding: 40px 20px; text-align: center; color: white;
        }
        .profile-avatar-lg {
            width: 110px; height: 110px; background: white; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 3.5rem; color: #2b3035; margin: 0 auto 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2); border: 4px solid rgba(255,255,255,0.3);
        }

        /* Forms */
        .form-section-title {
            font-size: 1.1rem; font-weight: 600; color: var(--agri-dark);
            margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0;
        }
        .form-label { font-size: 0.9rem; font-weight: 500; color: #666; margin-bottom: 5px; }
        .form-control {
            border-radius: 12px; padding: 12px 15px; border: 1px solid #eee; background-color: #fff;
        }
        .form-control:focus {
            background-color: white; border-color: var(--agri-green);
            box-shadow: 0 0 0 3px rgba(88, 129, 87, 0.1);
        }
        
        .btn-update {
            background-color: var(--agri-dark); color: white; border: none;
            border-radius: 50px; padding: 10px 25px; font-weight: 500; transition: 0.3s;
        }
        .btn-update:hover { background-color: #2f4a33; transform: translateY(-2px); color: white;}

        .btn-password {
            background-color: #e63946; color: white; border: none;
            border-radius: 50px; padding: 10px 25px; font-weight: 500; transition: 0.3s;
        }
        .btn-password:hover { background-color: #d62828; transform: translateY(-2px); color: white;}
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <?php include 'mobile_menu.php'; ?>

    <main class="main-content">
        <div class="container-fluid p-0">
            
            <div class="d-flex align-items-center mb-4">
                <h3 class="fw-bold text-dark mb-0">จัดการโปรไฟล์</h3>
            </div>

            <?= $msg ?>

            <div class="row g-4 justify-content-center">
                
                <div class="col-lg-4">
                    <div class="profile-card h-100">
                        <div class="profile-header">
                            <div class="profile-avatar-lg">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <h4 class="fw-bold mb-1"><?= htmlspecialchars($user['name'] . ' ' . $user['surname']) ?></h4>
                            <span class="badge bg-white bg-opacity-25 rounded-pill px-3">
                                Administrator
                            </span>
                        </div>
                        <div class="p-4">
                            <div class="d-flex justify-content-between mb-3 border-bottom pb-3">
                                <span class="text-muted"><i class="fas fa-user-tag me-2"></i>Username</span>
                                <span class="fw-bold text-dark"><?= htmlspecialchars($user['username']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted"><i class="fas fa-clock me-2"></i>เป็นสมาชิกเมื่อ</span>
                                <span class="text-dark small fw-bold">
                                    <?php 
                                        if(isset($user['created_at'])) {
                                            echo date("d/m/Y", strtotime($user['created_at']));
                                        } else { echo "-"; }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="profile-card p-4 p-md-5">
                        
                        <h5 class="form-section-title"><i class="fas fa-id-card me-2"></i>แก้ไขข้อมูลส่วนตัว</h5>
                        
                        <form method="POST" class="mb-5">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">ชื่อจริง</label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">นามสกุล</label>
                                    <input type="text" name="surname" class="form-control" value="<?= htmlspecialchars($user['surname']) ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">ชื่อผู้ใช้งาน (Username) <span class="text-danger small">*ใช้สำหรับล็อกอิน</span></label>
                                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="submit" name="update_profile" class="btn btn-update">
                                    <i class="fas fa-save me-2"></i> บันทึกข้อมูลส่วนตัว
                                </button>
                            </div>
                        </form>

                        <h5 class="form-section-title text-danger border-danger border-opacity-25"><i class="fas fa-key me-2"></i>เปลี่ยนรหัสผ่าน</h5>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">รหัสผ่านปัจจุบัน <span class="text-danger">*</span></label>
                                <input type="password" name="current_password" class="form-control" required placeholder="ยืนยันรหัสผ่านเดิมเพื่อความปลอดภัย">
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">รหัสผ่านใหม่ <span class="text-danger">*</span></label>
                                    <input type="password" name="new_password" class="form-control" required placeholder="กำหนดรหัสผ่านใหม่">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ยืนยันรหัสผ่านใหม่ <span class="text-danger">*</span></label>
                                    <input type="password" name="confirm_password" class="form-control" required placeholder="กรอกอีกครั้ง">
                                </div>
                            </div>
                            <div class="text-end mt-4">
                                <button type="submit" name="change_password" class="btn btn-password">
                                    <i class="fas fa-lock me-2"></i> เปลี่ยนรหัสผ่าน
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>