<?php
session_start();
include "./db.php";

// 1. ตรวจสอบ Login
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit;
}

// 2. สร้าง CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['userid'];
$msg = "";

// --- 3. Query Data ---
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// --- 4. Change Password Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    
    // ตรวจสอบ CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security Warning: CSRF Token Mismatch");
    }

    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if (password_verify($current_pass, $user['password'])) {
        if ($new_pass === $confirm_pass) {
            $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
            $update_pass_sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($update_pass_sql);
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                // Log the change (ถ้ามีตาราง log)
                $log_sql = "INSERT INTO password_logs (user_id, changed_by_admin_id) VALUES (?, ?)";
                // เช็คว่ามีตารางนี้จริงไหมก่อน execute เพื่อกัน Error ถ้ายังไม่ได้สร้าง
                // ในที่นี้ผม comment ส่วน execute ไว้กัน error ถ้าตารางไม่มี
                /* $log_stmt = $conn->prepare($log_sql);
                if ($log_stmt) {
                    $log_stmt->bind_param("ii", $user_id, $user_id);
                    $log_stmt->execute();
                    $log_stmt->close();
                } 
                */
                $msg = "<div class='position-fixed top-0 start-50 translate-middle-x mt-3 z-3 alert alert-success rounded-pill shadow-sm px-4 fade show'><i class='fas fa-check-circle me-2'></i>เปลี่ยนรหัสผ่านสำเร็จ!</div>";
            }
        } else {
            $msg = "<div class='position-fixed top-0 start-50 translate-middle-x mt-3 z-3 alert alert-danger rounded-pill shadow-sm px-4 fade show'><i class='fas fa-exclamation-circle me-2'></i>รหัสผ่านใหม่ไม่ตรงกัน</div>";
        }
    } else {
        $msg = "<div class='position-fixed top-0 start-50 translate-middle-x mt-3 z-3 alert alert-danger rounded-pill shadow-sm px-4 fade show'><i class='fas fa-times-circle me-2'></i>รหัสผ่านปัจจุบันไม่ถูกต้อง</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>โปรไฟล์ผู้ใช้งาน</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { --primary: #3A5A40; --primary-dark: #344E41; --secondary: #588157; --accent: #A3B18A; --bg: #F3F6F4; --sidebar-width: 250px; }

        body { font-family: 'Sarabun', sans-serif; background-color: var(--bg); padding-bottom: 90px; color: #333; }
        h1, h2, h3, h4, h5, .font-head { font-family: 'Kanit', sans-serif; }
        
        /* --- Sidebar Style --- */
        .sidebar {
            width: var(--sidebar-width); height: 100vh; position: fixed; top: 0; left: 0;
            background-color: var(--primary); color: white; z-index: 1040;
            display: flex; flex-direction: column; padding: 20px 15px;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
        }
        .sidebar-brand {
            font-family: 'Kanit'; font-size: 1.5rem; font-weight: bold;
            display: flex; align-items: center; gap: 10px; margin-bottom: 30px;
            padding-left: 5px; color: #fff; text-decoration: none;
        }
        .nav-link-custom {
            display: flex; align-items: center; padding: 12px 20px;
            color: rgba(255,255,255,0.8); text-decoration: none;
            border-radius: 12px; margin-bottom: 8px; transition: 0.3s;
            font-family: 'Kanit'; font-size: 1rem;
        }
        .nav-link-custom:hover { background: rgba(255,255,255,0.1); color: #fff; transform: translateX(5px); }
        .nav-link-custom.active { background: white; color: var(--primary); font-weight: 600; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .nav-link-custom i { width: 25px; text-align: center; margin-right: 10px; font-size: 1.1rem; }

        /* --- Main Wrapper --- */
        .main-wrapper { transition: margin-left 0.3s; }
        @media (min-width: 992px) { 
            .main-wrapper { margin-left: var(--sidebar-width); } 
            body { padding-bottom: 0; }
            .container { max-width: 1200px; }
        }

        /* --- Header Style --- */
        .app-header {
            background-color: var(--primary); color: white; padding: 15px 20px;
            position: sticky; top: 0; z-index: 1020;
            box-shadow: 0 4px 15px rgba(58, 90, 64, 0.2);
            border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;
        }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.8); object-fit: cover; }
        
        @media (min-width: 992px) {
            .app-header {
                border-radius: 0; background-color: white; color: #333; padding: 15px 40px;
            }
            .user-avatar { border-color: #ddd; }
        }

        /* --- Custom Card & Profile --- */
        .custom-card {
            background: white; border: none; border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            overflow: hidden; height: 100%; transition: transform 0.2s;
        }
        .profile-cover { height: 120px; background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); position: relative; }
        .profile-avatar-container { margin-top: -60px; text-align: center; position: relative; z-index: 10; }
        .profile-avatar-lg {
            width: 120px; height: 120px; background: white; border-radius: 50%; 
            padding: 5px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); object-fit: cover;
        }

        /* --- Form Styles --- */
        .form-label { font-family: 'Kanit'; font-weight: 500; font-size: 0.9rem; color: #666; margin-bottom: 0.5rem; }
        .form-control { border-radius: 12px; padding: 12px 15px; border: 1px solid #eee; background: #fcfcfc; font-size: 0.95rem; }
        .form-control:focus { background: white; border-color: var(--accent); box-shadow: 0 0 0 4px rgba(163, 177, 138, 0.2); }
        .input-group-text { background: #f8f9fa; border: 1px solid #eee; border-radius: 12px 0 0 12px; border-right: none; color: #888; }
        .input-group .form-control { border-left: none; }
        
        .btn-save {
            background: var(--primary); color: white; border: none;
            border-radius: 12px; padding: 12px; font-family: 'Kanit'; font-weight: 500;
            width: 100%; transition: 0.2s;
        }
        .btn-save:hover { background: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 4px 10px rgba(58, 90, 64, 0.3); }

        /* --- Mobile Nav --- */
        .bottom-nav { position: fixed; bottom: 0; width: 100%; background: white; z-index: 1000; display: flex; justify-content: space-around; padding: 10px 0 20px; box-shadow: 0 -5px 20px rgba(0,0,0,0.05); border-radius: 25px 25px 0 0; }
        .nav-item-m { text-align: center; color: #bbb; text-decoration: none; font-size: 0.7rem; width: 60px; transition: 0.3s; }
        .nav-item-m i { font-size: 1.4rem; display: block; margin-bottom: 4px; }
        .nav-item-m.active { color: var(--primary); font-weight: 600; }
        
        .fab-center { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); width: 65px; height: 65px; background-color: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 -4px 10px rgba(0,0,0,0.1); border: 5px solid #f3f6f4; z-index: 1050; text-decoration: none; transition: transform 0.2s; }
        .fab-center:active { transform: translateX(-50%) scale(0.95); }
        @media (min-width: 992px) { .bottom-nav, .fab-center { display: none !important; } }
    </style>
</head>
<body>
    
    <aside class="sidebar d-none d-lg-flex">
        <a href="index.php" class="sidebar-brand">
            <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="fas fa-leaf"></i>
            </div>
            <div class="d-flex flex-column justify-content-center">
                <div style="line-height: 1; font-size: 1.2rem;">สมุดบันทึก</div>
                <div style="font-size: 0.75rem; opacity: 0.7; font-weight: normal; margin-top: 4px;">หมู่บ้านแม่ต๋ำต้นโพธิ์</div>
            </div>
        </a>

        <div class="px-2 mb-4">
            <a href="savedata.php" class="btn w-100 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm" 
               style="background: #A3B18A; color: #fff; border: none; padding: 12px; border-radius: 15px; transition: 0.2s;">
                <i class="fas fa-plus-circle fa-lg"></i> บันทึกข้อมูล
            </a>
        </div>

        <div class="mt-2">
            <small class="text-white-50 ms-3 mb-2 d-block" style="font-size: 0.75rem;">MENU</small>
            
            <a href="index.php" class="nav-link-custom">
                <i class="fas fa-home"></i> หน้าหลัก
            </a>
            <a href="calendar.php" class="nav-link-custom">
                <i class="fas fa-calendar-alt"></i> ปฏิทิน
            </a>
            <a href="history.php" class="nav-link-custom">
                <i class="fas fa-history"></i> ประวัติ
            </a>
            <a href="profile.php" class="nav-link-custom active">
                <i class="fas fa-user-circle"></i> โปรไฟล์
            </a>
        </div>

        <div class="mt-auto">
            <a href="logout.php" class="nav-link-custom text-danger" style="background: rgba(255,255,255,0.9);">
                <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
            </a>
        </div>
    </aside>

    <div class="main-wrapper">
        <?= $msg ?>

        <header class="app-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-4">
                <div class="d-flex align-items-center gap-2 d-lg-none">
                    <div style="width: 35px; height: 35px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div>
                        <div class="font-head fw-bold lh-1" style="font-size: 1.1rem;">ข้อมูลส่วนตัว</div>
                        <div style="font-size: 0.75rem; opacity: 0.8;">จัดการบัญชีของคุณ</div>
                    </div>
                </div>

                <div class="d-none d-lg-block">
                    <h4 class="font-head fw-bold mb-0 text-dark">ข้อมูลส่วนตัว</h4>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                 <a href="savedata.php" class="btn btn-success fw-bold d-none d-lg-block rounded-pill px-4 shadow-sm">
                    <i class="fas fa-plus me-2"></i>เพิ่มข้อมูล
                </a>

                <div class="dropdown">
                    <a href="#" data-bs-toggle="dropdown">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['fullname']) ?>&background=random&color=<?= isset($_SESSION['userid'])?'fff':'333' ?>" class="user-avatar shadow-sm">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 mt-2">
                        <li><span class="dropdown-header">ยินดีต้อนรับ, <?= htmlspecialchars($user['name']) ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item small text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="container pt-4">
            <div class="row g-4 justify-content-center">
                
                <div class="col-lg-4 col-md-5">
                    <div class="custom-card pb-4">
                        <div class="profile-cover"></div>
                        <div class="profile-avatar-container">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['name']) ?>&length=1&font-size=0.4&size=200&background=fff&color=3A5A40&bold=true" class="profile-avatar-lg">
                        </div>
                        <div class="text-center mt-3 px-3">
                            <h4 class="font-head fw-bold mb-1"><?= htmlspecialchars($user['name'] . ' ' . $user['surname']) ?></h4>
                            <p class="text-muted small mb-2">@<?= htmlspecialchars($user['username']) ?></p>
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2 fw-normal border border-success border-opacity-25">
                                <i class="fas fa-seedling me-1"></i> เกษตรกร (<?= htmlspecialchars(ucfirst($user['role'])) ?>)
                            </span>
                            
                            <div class="mt-4 pt-3 border-top d-flex justify-content-around">
                                <div class="text-center">
                                    <small class="d-block text-muted" style="font-size: 0.7rem;">วันที่เข้าร่วม</small>
                                    <span class="font-head text-primary fw-bold">
                                        <?php 
                                            $d = strtotime($user['created_at']);
                                            echo date("d/m/", $d) . (date("Y", $d)+543);
                                        ?>
                                    </span>
                                </div>
                                <div class="text-center">
                                    <small class="d-block text-muted" style="font-size: 0.7rem;">สถานะ</small>
                                    <span class="font-head text-success fw-bold"><i class="fas fa-check-circle me-1"></i>ปกติ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <a href="logout.php" class="btn btn-outline-danger w-100 mt-3 rounded-4 py-2 border-0 shadow-sm bg-white d-md-none fw-bold">
                        <i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ
                    </a>
                </div>

                <div class="col-lg-7 col-md-7">
                    <div class="custom-card p-4">
                        <h5 class="font-head fw-bold mb-4 text-primary border-bottom pb-2"><i class="fas fa-id-card me-2"></i>ข้อมูลพื้นฐาน</h5>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label">ชื่อจริง</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" readonly style="background-color: #fff;">
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label">นามสกุล</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['surname']) ?>" readonly style="background-color: #fff;">
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">บัญชีผู้ใช้ (Username)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-at"></i></span>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" readonly style="background-color: #fff;">
                                </div>
                            </div>
                        </div>

                        <hr class="my-4 border-light">

                        <h5 class="font-head fw-bold mb-4 text-danger border-bottom pb-2"><i class="fas fa-shield-alt me-2"></i>ความปลอดภัย</h5>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                            <div class="mb-3">
                                <label class="form-label">รหัสผ่านปัจจุบัน <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock-open"></i></span>
                                    <input type="password" name="current_password" class="form-control" required placeholder="กรอกรหัสเดิมเพื่อยืนยัน">
                                </div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">รหัสผ่านใหม่</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                                        <input type="password" name="new_password" class="form-control" required placeholder="ตั้งรหัสผ่านใหม่">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-check-double"></i></span>
                                        <input type="password" name="confirm_password" class="form-control" required placeholder="พิมพ์ซ้ำอีกครั้ง">
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="submit" name="change_password" class="btn btn-save shadow-sm">
                                    <i class="fas fa-save me-2"></i>บันทึกการเปลี่ยนแปลง
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div> <a href="savedata.php" class="fab-center d-lg-none">
        <i class="fas fa-plus"></i>
    </a>

    <div class="bottom-nav d-lg-none">
        <a href="index.php" class="nav-item-m">
            <i class="fas fa-home"></i>หน้าหลัก
        </a>
        <a href="calendar.php" class="nav-item-m">
            <i class="fas fa-calendar-alt"></i>ปฏิทิน
        </a>
        
        <div style="width: 60px;"></div>

        <a href="history.php" class="nav-item-m">
            <i class="fas fa-history"></i>ประวัติ
        </a>
        <a href="profile.php" class="nav-item-m active">
            <i class="fas fa-user"></i>โปรไฟล์
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto hide alert
        setTimeout(function() {
            let alert = document.querySelector('.alert');
            if(alert) {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 500);
            }
        }, 3000);
    </script>
</body>
</html>