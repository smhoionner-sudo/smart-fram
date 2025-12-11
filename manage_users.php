<?php
session_start();
include "./db.php";

// 1. Check Admin
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// ดึงชื่อ Admin (จำเป็นต้องมีเพื่อให้ sidebar.php แสดงชื่อได้ถูกต้อง)
$admin_id = $_SESSION['userid'];
$stmt_ad = $conn->prepare("SELECT name, surname FROM users WHERE id = ?");
$stmt_ad->bind_param("i", $admin_id);
$stmt_ad->execute();
$res_ad = $stmt_ad->get_result()->fetch_assoc();
$admin_fullname = ($res_ad['name'] ?? 'Admin') . ' ' . ($res_ad['surname'] ?? '');

$alert_message = "";
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// --- CRUD Logic (เหมือนเดิม) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_user'])) {
    $id = $_POST['user_id'];
    $username = trim($_POST['username']);
    $name = trim($_POST['name']);
    $surname = trim($_POST['surname']);
    $role = $_POST['role'];
    $status = $_POST['status'];
    $password = $_POST['password'];

    $checkSQL = "SELECT id FROM users WHERE username = ? AND id != ?";
    $stmtCheck = $conn->prepare($checkSQL);
    $checkID = ($id) ? $id : 0;
    $stmtCheck->bind_param("si", $username, $checkID);
    $stmtCheck->execute();
    
    if ($stmtCheck->get_result()->num_rows > 0) {
        $alert_message = '<div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4"><i class="fas fa-exclamation-circle me-2"></i>ชื่อผู้ใช้ซ้ำ</div>';
    } else {
        if ($id) { // Update
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET username=?, password=?, name=?, surname=?, role=?, status=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssi", $username, $hashed_password, $name, $surname, $role, $status, $id);
            } else {
                $sql = "UPDATE users SET username=?, name=?, surname=?, role=?, status=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssi", $username, $name, $surname, $role, $status, $id);
            }
            if ($stmt->execute()) $alert_message = '<div class="alert alert-success shadow-sm border-0 rounded-3 mb-4"><i class="fas fa-check-circle me-2"></i>บันทึกสำเร็จ</div>';
        } else { // Insert
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (username, password, name, surname, role, status) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssss", $username, $hashed_password, $name, $surname, $role, $status);
                if ($stmt->execute()) $alert_message = '<div class="alert alert-success shadow-sm border-0 rounded-3 mb-4"><i class="fas fa-check-circle me-2"></i>เพิ่มสำเร็จ</div>';
            } else {
                $alert_message = '<div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4"><i class="fas fa-exclamation-triangle me-2"></i>กรุณาใส่รหัสผ่าน</div>';
            }
        }
    }
}

if (isset($_GET['delete_id'])) {
    $del_id = $_GET['delete_id'];
    if ($del_id != $_SESSION['userid']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $del_id);
        if ($stmt->execute()) $alert_message = '<div class="alert alert-dark shadow-sm border-0 rounded-3 mb-4"><i class="fas fa-trash-alt me-2"></i>ลบเรียบร้อย</div>';
    } else {
        $alert_message = '<div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4">ลบบัญชีตัวเองไม่ได้</div>';
    }
}

// --- Pagination Logic ---
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;
$start = ($page - 1) * $limit;

$count_sql = "SELECT COUNT(*) as total FROM users WHERE (username LIKE ? OR name LIKE ? OR surname LIKE ?)";
$search_param = "%{$search}%";
$stmtCount = $conn->prepare($count_sql);
$stmtCount->bind_param("sss", $search_param, $search_param, $search_param);
$stmtCount->execute();
$total_rows = $stmtCount->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$sql = "SELECT * FROM users WHERE (username LIKE ? OR name LIKE ? OR surname LIKE ?) ORDER BY id DESC LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssii", $search_param, $search_param, $search_param, $start, $limit);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>จัดการผู้ใช้งาน</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-body: #f3f4f6; --card-bg: #ffffff; --text-primary: #1f2937; --text-secondary: #6b7280;
            --border-color: #e5e7eb; --agri-green: #588157; --agri-dark: #3a5a40;
            --sidebar-width: 260px; --sidebar-bg: #2b3035;
        }
        body { font-family: 'Kanit', sans-serif; background-color: var(--bg-body); color: var(--text-primary); padding-bottom: 80px; }

        /* --- Sidebar & Layout Styles (จำเป็นต้องมีเพื่อให้ไฟล์ include แสดงผลถูกต้อง) --- */
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

        /* --- UI Elements --- */
        .card-modern { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); overflow: hidden; }
        .badge-status { padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
        .bg-active { background: #dcfce7; color: #166534; }
        .bg-inactive { background: #f3f4f6; color: #6b7280; }
        .badge-role { padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 500; }
        .role-admin { background: #1f2937; color: white; }
        .role-user { background: #e5e7eb; color: #374151; }

        /* Desktop Table */
        .table-custom th { background: #f9fafb; font-weight: 500; font-size: 0.85rem; padding: 15px; border-bottom: 1px solid var(--border-color); }
        .table-custom td { padding: 15px; vertical-align: middle; border-bottom: 1px solid var(--border-color); }

        /* Mobile User Card */
        .user-card-mobile { background: white; border-radius: 16px; padding: 15px; margin-bottom: 15px; border: 1px solid var(--border-color); box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .user-card-mobile:active { transform: scale(0.98); transition: 0.2s; }

        /* Floating Action Button (Mobile) */
        .fab-add { position: fixed; bottom: 25px; right: 25px; width: 60px; height: 60px; background: var(--agri-dark); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.2); z-index: 1050; border: none; transition: 0.2s; }
        .fab-add:active { transform: scale(0.9); }

        .btn-icon-sm { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid #eee; background: white; color: #666; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <?php include 'mobile_menu.php'; ?>

    <main class="main-content">
        <div class="container-fluid p-0">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold text-dark d-none d-lg-block mb-0">จัดการผู้ใช้งาน</h3>
                <button class="btn btn-dark d-none d-lg-block rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetForm()">
                    <i class="fas fa-plus me-2"></i>เพิ่มผู้ใช้งาน
                </button>
            </div>

            <?= $alert_message ?>

            <div class="card-modern p-3 mb-4">
                <form method="GET" class="row g-2">
                    <div class="col-9 col-md-10">
                        <input type="text" name="search" class="form-control bg-light border-0" placeholder="ค้นหาชื่อ, Username..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-3 col-md-2">
                        <a href="manage_users.php" class="btn btn-light border w-100"><i class="fas fa-sync-alt"></i></a>
                    </div>
                </form>
            </div>

            <div class="card-modern d-none d-lg-block mb-4">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>User Info</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if($result->num_rows > 0):
                            while($row = $result->fetch_assoc()): 
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-secondary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                            <?= strtoupper(mb_substr($row['name'], 0, 1)) ?>
                                        </div>
                                        <div class="fw-bold"><?= htmlspecialchars($row['name'].' '.$row['surname']) ?></div>
                                    </div>
                                </td>
                                <td class="text-secondary"><?= htmlspecialchars($row['username']) ?></td>
                                <td>
                                    <span class="badge-role <?= $row['role']=='admin'?'role-admin':'role-user' ?>">
                                        <?= strtoupper($row['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-status <?= $row['status']=='active'?'bg-active':'bg-inactive' ?>">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-light btn-sm border me-1" onclick="editUser(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)"><i class="fas fa-pen text-warning"></i></button>
                                    <a href="?delete_id=<?= $row['id'] ?>" class="btn btn-light btn-sm border" onclick="return confirm('ยืนยันลบ?')"><i class="fas fa-trash text-danger"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">ไม่พบข้อมูล</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-lg-none">
                <?php 
                $result->data_seek(0);
                if($result->num_rows > 0):
                    while($row = $result->fetch_assoc()): 
                ?>
                <div class="user-card-mobile">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; font-size: 1.1rem;">
                                <?= strtoupper(mb_substr($row['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($row['name'].' '.$row['surname']) ?></div>
                                <div class="text-muted small">@<?= htmlspecialchars($row['username']) ?></div>
                            </div>
                        </div>
                        <span class="badge-status <?= $row['status']=='active'?'bg-active':'bg-inactive' ?>">
                            <?= ucfirst($row['status']) ?>
                        </span>
                    </div>
                    <hr class="my-2 border-light">
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <span class="badge-role <?= $row['role']=='admin'?'role-admin':'role-user' ?>">
                            <i class="fas fa-user-shield me-1"></i> <?= strtoupper($row['role']) ?>
                        </span>
                        <div>
                            <button class="btn btn-icon-sm me-2" onclick="editUser(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)">
                                <i class="fas fa-pen text-warning"></i>
                            </button>
                            <a href="?delete_id=<?= $row['id'] ?>" class="btn btn-icon-sm" onclick="return confirm('ยืนยันลบ?')">
                                <i class="fas fa-trash text-danger"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; else: ?>
                    <div class="text-center py-5 text-muted">ไม่พบข้อมูล</div>
                <?php endif; ?>
            </div>

            <?php if($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link rounded-pill px-3 border-0 shadow-sm me-1" href="?page=<?= $page-1 ?>&search=<?= $search ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link rounded-pill px-3 border-0 shadow-sm me-1 <?= ($page == $i) ? 'bg-dark border-dark' : 'text-dark' ?>" href="?page=<?= $i ?>&search=<?= $search ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link rounded-pill px-3 border-0 shadow-sm" href="?page=<?= $page+1 ?>&search=<?= $search ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>

        </div>
    </main>

    <button class="fab-add d-lg-none" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetForm()">
        <i class="fas fa-plus"></i>
    </button>

    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="modalTitle">เพิ่มผู้ใช้งาน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body pt-0">
                        <input type="hidden" name="user_id" id="user_id">
                        <div class="mb-3">
                            <label class="form-label small text-muted">Username</label>
                            <input type="text" name="username" id="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted">รหัสผ่าน <span id="passwordHint" class="fw-light"></span></label>
                            <input type="password" name="password" id="password" class="form-control">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small text-muted">ชื่อ</label>
                                <input type="text" name="name" id="name" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted">นามสกุล</label>
                                <input type="text" name="surname" id="surname" class="form-control" required>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small text-muted">สิทธิ์</label>
                                <select name="role" id="role" class="form-select">
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted">สถานะ</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 justify-content-between px-4 pb-4">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="save_user" class="btn btn-dark rounded-pill px-4">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetForm() {
            document.getElementById('modalTitle').innerText = 'เพิ่มผู้ใช้งาน';
            document.getElementById('user_id').value = '';
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            document.getElementById('password').required = true;
            document.getElementById('passwordHint').innerText = '*';
            document.getElementById('name').value = '';
            document.getElementById('surname').value = '';
            document.getElementById('role').value = 'user';
            document.getElementById('status').value = 'active';
        }
        function editUser(data) {
            var myModal = new bootstrap.Modal(document.getElementById('userModal'));
            myModal.show();
            document.getElementById('modalTitle').innerText = 'แก้ไขข้อมูล';
            document.getElementById('user_id').value = data.id;
            document.getElementById('username').value = data.username;
            document.getElementById('password').value = ''; 
            document.getElementById('password').required = false; 
            document.getElementById('passwordHint').innerText = '(เว้นว่างถ้าไม่เปลี่ยน)';
            document.getElementById('name').value = data.name;
            document.getElementById('surname').value = data.surname;
            document.getElementById('role').value = data.role;
            document.getElementById('status').value = data.status;
        }
    </script>
</body>
</html>