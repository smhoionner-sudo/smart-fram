<?php
session_start();
include "./db.php";

// Check Admin
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// ดึงข้อมูล Log
$sql = "SELECT 
            logs.created_at as change_date,
            u_target.username as target_user,
            u_target.name as target_name,
            u_target.surname as target_surname,
            u_admin.username as admin_user,
            u_admin.name as admin_name
        FROM password_logs logs
        JOIN users u_target ON logs.user_id = u_target.id
        JOIN users u_admin ON logs.changed_by_admin_id = u_admin.id
        ORDER BY logs.created_at DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการเปลี่ยนรหัสผ่าน</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-body: #f3f4f6;
            --card-bg: #ffffff;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
            --agri-dark: #3a5a40;
        }

        body { font-family: 'Kanit', sans-serif; background-color: var(--bg-body); color: var(--text-primary); padding-bottom: 50px; }

        /* Header & Back Button */
        .top-header { padding-top: 30px; padding-bottom: 20px; }
        .btn-back {
            background-color: white; border: 1px solid var(--border-color); color: var(--text-secondary);
            width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
            transition: all 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.03);
        }
        .btn-back:hover { background-color: #f9fafb; color: var(--text-primary); transform: translateX(-3px); }

        /* Card Modern */
        .card-modern {
            background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02); transition: all 0.2s ease; overflow: hidden;
        }
        .card-modern:hover { box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.05); }

        /* Table */
        .table-custom th {
            background-color: #f9fafb; color: var(--text-secondary); font-weight: 500; font-size: 0.8rem;
            text-transform: uppercase; padding: 15px 20px; border-bottom: 1px solid var(--border-color);
        }
        .table-custom td {
            padding: 15px 20px; vertical-align: middle; border-bottom: 1px solid var(--border-color); color: #374151; font-size: 0.95rem;
        }
        .table-custom tr:last-child td { border-bottom: none; }
        .table-custom tr:hover { background-color: #f9fafb; }

        /* Avatar */
        .user-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background-color: #e5e7eb; color: #4b5563;
            display: flex; align-items: center; justify-content: center;
            font-weight: 600; font-size: 1rem;
        }

        /* Badge */
        .badge-admin {
            background-color: #f3f4f6; color: #1f2937;
            padding: 5px 12px; border-radius: 50px; border: 1px solid #d1d5db;
            font-weight: 500; font-size: 0.8rem; display: inline-flex; align-items: center;
        }
    </style>
</head>
<body>

    <div class="container top-header">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center">
                <a href="admin_dashboard.php" class="btn-back me-3" title="กลับหน้า Dashboard">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h3 class="fw-bold text-dark mb-0">ประวัติการเปลี่ยนรหัสผ่าน</h3>
                    <span class="text-secondary small">ตรวจสอบรายการ (Audit Log) การรีเซ็ตรหัสผ่านโดยแอดมิน</span>
                </div>
            </div>
            
            <div class="d-none d-md-block">
                <span class="badge bg-white text-secondary border px-3 py-2 rounded-pill fw-normal shadow-sm">
                    <i class="fas fa-shield-alt me-2 text-dark"></i>Security Log
                </span>
            </div>
        </div>

        <div class="card-modern">
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>วันและเวลา</th>
                            <th>ผู้ใช้ที่ถูกเปลี่ยนรหัส (Target User)</th>
                            <th>ดำเนินการโดย (Admin)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center text-dark fw-medium">
                                            <i class="far fa-clock me-2 text-secondary"></i>
                                            <?= date("d/m/Y", strtotime($row['change_date'])) ?>
                                            <span class="text-secondary ms-2 small fw-normal">
                                                <?= date("H:i", strtotime($row['change_date'])) ?> น.
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-3 shadow-sm bg-white border text-primary">
                                                <?= strtoupper(mb_substr($row['target_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark">
                                                    <?= htmlspecialchars($row['target_name'].' '.$row['target_surname']) ?>
                                                </div>
                                                <div class="text-secondary small">Username: <?= htmlspecialchars($row['target_user']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="badge-admin">
                                            <i class="fas fa-user-shield me-2 text-secondary"></i>
                                            <?= htmlspecialchars($row['admin_name'] ?? $row['admin_user']) ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-5">
                                    <div class="opacity-25 mb-3"><i class="fas fa-history fa-3x"></i></div>
                                    <p class="text-secondary">ยังไม่มีประวัติการเปลี่ยนรหัสผ่านในระบบ</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>