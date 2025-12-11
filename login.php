<?php 
// --- 1. การตั้งค่าความปลอดภัย Session และ Cookie ---
$secure_connection = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secure_connection,
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();
include "./db.php"; 

// --- 2. Security Headers ---
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Content-Type: text/html; charset=UTF-8");

// ==========================================
// ★ ส่วนที่เพิ่ม: ฟังก์ชันจัดการ Login Attempts
// ==========================================
function getClientIP() {
    return $_SERVER['REMOTE_ADDR'];
}

function checkBruteForce($conn, $ip) {
    // นับจำนวนที่ผิดใน 10 นาทีที่ผ่านมา
    $sql = "SELECT COUNT(*) as count FROM login_attempts WHERE ip_address = ? AND attempt_time > (NOW() - INTERVAL 10 MINUTE)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['count'];
}

function logFailedLogin($conn, $ip) {
    $sql = "INSERT INTO login_attempts (ip_address, attempt_time) VALUES (?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $ip);
    $stmt->execute();
}

function resetLoginAttempts($conn, $ip) {
    $sql = "DELETE FROM login_attempts WHERE ip_address = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $ip);
    $stmt->execute();
}
// ==========================================

$remember_username = "";
if (isset($_COOKIE['remember_username'])) {
    $remember_username = $_COOKIE['remember_username'];
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_ip = getClientIP();
    $failed_attempts = checkBruteForce($conn, $current_ip);

    // ★ เช็คก่อนเลยว่าโดนแบนหรือยัง (เกิน 5 ครั้งไหม)
    if ($failed_attempts >= 5) {
        $error = "คุณกรอกรหัสผิดเกินจำนวนครั้งที่กำหนด กรุณารอ 10 นาทีแล้วลองใหม่";
    } else {
        // ถ้ายังไม่โดนแบน ให้เริ่มเช็ค Login
        $username = trim($_POST['username']); 
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? $_POST['remember'] : '';
    
        $sql = "SELECT * FROM users WHERE username = ? AND status = 'active' LIMIT 1";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $username); 
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
    
                if (password_verify($password, $row['password'])) {
                    // --- Login สำเร็จ ---
                    
                    // 1. ล้างประวัติการทำผิดของ IP นี้ทิ้ง
                    resetLoginAttempts($conn, $current_ip);
    
                    session_regenerate_id(true); 
                    $_SESSION['userid'] = $row['id']; 
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role'] = $row['role'];
                    $_SESSION['fullname'] = $row['name'] . ' ' . $row['surname']; 
    
                    // จัดการ Cookie จำชื่อผู้ใช้
                    if ($remember == 'on') {
                        setcookie('remember_username', $username, time() + (86400 * 30), "/");
                    } else {
                        setcookie('remember_username', '', time() - 3600, "/");
                    }
    
                    if ($row['role'] == 'admin') {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: index.php");
                    }
                    exit;
                } else {
                    // --- รหัสผ่านผิด ---
                    logFailedLogin($conn, $current_ip); // บันทึกการทำผิด
                    sleep(1); 
                    $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง (เหลือโอกาส " . (4 - $failed_attempts) . " ครั้ง)";
                }
            } else {
                // --- หาชื่อผู้ใช้ไม่เจอ ---
                logFailedLogin($conn, $current_ip); // บันทึกการทำผิดเช่นกัน (เพื่อกันเดา User)
                sleep(1); 
                $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง (เหลือโอกาส " . (4 - $failed_attempts) . " ครั้ง)";
            }
            $stmt->close();
        } else {
            $error = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล";
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - สมุดบันทึกการเกษตร</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-image: url('https://images.unsplash.com/photo-1500382017468-9049fed747ef');
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-card {
            max-width: 420px;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 20px;
            border: 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            padding: 2.5rem 2.5rem;
        }

        .login-card .icon-header {
            font-size: 3.5rem;
            color: #588157; 
            margin-bottom: 1rem;
        }

        .login-card .card-title {
            font-weight: 600; 
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            color: #3A5A40; 
        }
        
        .login-card .card-subtitle {
            color: #6c757d;
            margin-bottom: 1.5rem;
            font-weight: 300;
        }
        
        .form-control {
            border: 1px solid #ced4da;
            font-size: 1rem;
            font-weight: 400;
            padding: 0.75rem 1rem;
        }
        
        .input-group > .form-control {
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
        }
        .input-group > .btn {
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
            border: 1px solid #ced4da;
            border-left: 0;
            color: #6c757d;
        }
        .input-group > .btn:hover {
            background-color: #f8f9fa;
            color: #588157;
        }

        .form-control:focus {
            border-color: #588157;
            box-shadow: 0 0 0 0.25rem rgba(88, 129, 87, 0.2);
            z-index: 1;
        }

        .btn-custom {
            background: #588157; 
            color: white;
            font-weight: 500;
            padding: 0.75rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            border: 0;
            font-size: 1.1rem;
        }
        .btn-custom:hover {
            background: #3A5A40; 
            color: #fff;
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .forgot-link {
            font-size: 0.9rem;
            text-decoration: none;
            color: #588157; 
            font-weight: 500;
        }
        .forgot-link:hover {
            text-decoration: underline;
            color: #3A5A40;
        }
        
        .form-check-input:checked {
            background-color: #588157;
            border-color: #588157;
        }
    </style>
</head>
<body>

    <div class="card login-card">
        
        <div class="text-center icon-header">
            <i class="fas fa-seedling"></i>
        </div>
        
        <h3 class="text-center card-title">สมุดบันทึกการเกษตร</h3>
        <p class="text-center card-subtitle">กรุณาเข้าสู่ระบบเพื่อใช้งาน</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 text-center"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label fw-bold" style="color: #3A5A40;">ชื่อผู้ใช้</label>
                <input type="text" class="form-control" name="username" 
                       value="<?= htmlspecialchars($remember_username) ?>" 
                       style="border-radius: 10px;" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold" style="color: #3A5A40;">รหัสผ่าน</label>
                <div class="input-group">
                    <input type="password" class="form-control" name="password" id="passwordInput" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" name="remember" id="rememberCheck" 
                       <?php if(isset($_COOKIE['remember_username'])) echo "checked"; ?>>
                <label class="form-check-label text-secondary" for="rememberCheck" style="font-size: 0.9rem;">
                    จดจำชื่อผู้ใช้
                </label>
            </div>

            <button type="submit" class="btn btn-custom w-100 mt-1">เข้าสู่ระบบ</button>
            
            <div class="text-center mt-3 pt-2">
                <a href="forgot.php" class="forgot-link">ลืมรหัสผ่าน?</a>
            </div>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
    
    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#passwordInput');

        togglePassword.addEventListener('click', function (e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>