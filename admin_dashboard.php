<?php
session_start();
include "./db.php";

// --- ตรวจสอบสิทธิ์ ---
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// ดึงชื่อ Admin (ตัวแปรนี้จะถูกใช้ใน sidebar.php ด้วย)
$admin_id = $_SESSION['userid'];
$stmt_ad = $conn->prepare("SELECT name, surname FROM users WHERE id = ?");
$stmt_ad->bind_param("i", $admin_id);
$stmt_ad->execute();
$res_ad = $stmt_ad->get_result()->fetch_assoc();
$admin_fullname = ($res_ad['name'] ?? 'Admin') . ' ' . ($res_ad['surname'] ?? '');

// --- 1. สถิติรวม (Stats) ---
$total_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'")->fetch_assoc()['total'];
$total_logs = $conn->query("SELECT COUNT(*) as total FROM agricultural_logs")->fetch_assoc()['total'];
$total_events = $conn->query("SELECT COUNT(*) as total FROM calendar_events")->fetch_assoc()['total'];

// --- 2. ข้อมูลกราฟ ---
$sql_crop = "SELECT crop_type, COUNT(*) as count FROM agricultural_logs GROUP BY crop_type";
$res_crop = $conn->query($sql_crop);
$crop_labels = []; $crop_data = []; $crop_colors = [];
while($row = $res_crop->fetch_assoc()) {
    $label = ($row['crop_type']=='rice'?'ข้าว':($row['crop_type']=='longan'?'ลำไย':'ยางพารา'));
    $crop_labels[] = $label;
    $crop_data[] = $row['count'];
    if($row['crop_type']=='rice') $crop_colors[] = '#A3B18A';
    elseif($row['crop_type']=='longan') $crop_colors[] = '#D4A373';
    else $crop_colors[] = '#52796F';
}
$sql_recent = "SELECT * FROM users WHERE role='user' ORDER BY created_at DESC LIMIT 5";
$result_recent = $conn->query($sql_recent);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin Dashboard</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --bg-body: #f3f4f6; --card-bg: #ffffff; --text-primary: #1f2937; --text-secondary: #6b7280;
            --border-color: #e5e7eb; --agri-green: #588157; --agri-dark: #3a5a40;
            --sidebar-width: 260px; --sidebar-bg: #2b3035;
            --agri-light: #e9f5db;
        }

        body { font-family: 'Kanit', sans-serif; background-color: var(--bg-body); color: var(--text-primary); padding-bottom: 50px; }

        /* --- CSS สำหรับ Sidebar/Mobile Menu (จำเป็นต้องมีเพื่อให้ไฟล์ include แสดงผลถูกต้อง) --- */
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

        /* --- Dashboard Specific Components --- */
        .card-modern { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); padding: 20px; height: 100%; transition: 0.2s; }
        .card-modern:hover { transform: translateY(-3px); box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.05); }
        
        .stat-value { font-size: 2rem; font-weight: 600; color: var(--text-primary); }
        .stat-label { font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 500; }
        .stat-icon { width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; }
        .icon-users { background: #f0f9ff; color: #0ea5e9; }
        .icon-logs { background: var(--agri-light); color: var(--agri-green); }
        .icon-events { background: #fff7ed; color: #ea580c; }
        
        .table-custom th { background: #f9fafb; font-weight: 500; font-size: 0.8rem; padding: 15px; border-bottom: 1px solid var(--border-color); }
        .table-custom td { padding: 15px; vertical-align: middle; border-bottom: 1px solid var(--border-color); }
        .badge-status { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
        .bg-active { background: #dcfce7; color: #166534; }
        .bg-inactive { background: #f3f4f6; color: #6b7280; }

        .btn-action { display: flex; flex-direction: column; align-items: center; background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 15px; color: var(--text-secondary); text-decoration: none; transition: 0.2s; height: 100%; }
        .btn-action:hover { background: var(--sidebar-bg); color: white; border-color: var(--sidebar-bg); transform: translateY(-2px); }
        .btn-action i { font-size: 1.6rem; margin-bottom: 8px; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <?php include 'mobile_menu.php'; ?>

    <main class="main-content">
        <div class="container-fluid p-0">
            
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">ภาพรวมระบบ</h3>
                    <p class="text-secondary mb-0">ข้อมูลล่าสุด ณ วันที่ <?= date('d/m/Y') ?></p>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card-modern">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">สมาชิกทั้งหมด</div>
                                <div class="stat-value"><?= number_format($total_users) ?></div>
                                <div class="text-secondary small mt-1">Users</div>
                            </div>
                            <div class="stat-icon icon-users"><i class="fas fa-users"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-modern">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">บันทึกการผลิต</div>
                                <div class="stat-value"><?= number_format($total_logs) ?></div>
                                <div class="text-secondary small mt-1">Records</div>
                            </div>
                            <div class="stat-icon icon-logs"><i class="fas fa-clipboard-list"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-modern">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">ปฏิทินกิจกรรม</div>
                                <div class="stat-value"><?= number_format($total_events) ?></div>
                                <div class="text-secondary small mt-1">Events</div>
                            </div>
                            <div class="stat-icon icon-events"><i class="fas fa-calendar-day"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-lg-8">
                    <div class="card-modern">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold m-0 fs-6"><i class="fas fa-chart-pie me-2 text-secondary"></i>สัดส่วนการเพาะปลูกในระบบ</h5>
                        </div>
                        <div style="height: 280px;">
                            <canvas id="cropChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card-modern">
                        <h5 class="fw-bold text-dark mb-4 fs-6"><i class="fas fa-th-large me-2 text-secondary"></i>เมนูด่วน</h5>
                        <div class="row g-3">
                            <div class="col-6"><a href="manage_users.php" class="btn-action"><i class="fas fa-user-cog"></i><span>จัดการสมาชิก</span></a></div>
                            <div class="col-6"><a href="view_all_production.php" class="btn-action"><i class="fas fa-list-ul"></i><span>รายการผลิต</span></a></div>
                            <div class="col-6"><a href="edit-production_admin.php" class="btn-action"><i class="fas fa-edit"></i><span>แก้ไขข้อมูล</span></a></div>
                            <div class="col-6"><a href="admin_calendar.php" class="btn-action"><i class="fas fa-calendar-plus"></i><span>ลงประกาศ</span></a></div>
                            <div class="col-12"><a href="admin_slider.php" class="btn-action flex-row gap-3"><i class="fas fa-images mb-0"></i><span>จัดการป้ายข่าว</span></a></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-modern p-0 overflow-hidden mb-5">
                <div class="p-4 border-bottom d-flex justify-content-between align-items-center bg-white">
                    <h5 class="fw-bold m-0 text-dark fs-6"><i class="fas fa-user-clock me-2 text-secondary"></i>สมาชิกใหม่ล่าสุด</h5>
                    <a href="manage_users.php" class="btn btn-sm btn-light border text-secondary rounded-pill px-3">ดูทั้งหมด</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>ชื่อ-นามสกุล</th>
                                <th>Username</th>
                                <th>วันที่สมัคร</th>
                                <th>สถานะ</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_recent->num_rows > 0): ?>
                                <?php while($row = $result_recent->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                    <?= strtoupper(mb_substr($row['name'], 0, 1)) ?>
                                                </div>
                                                <div class="fw-medium text-dark"><?= htmlspecialchars($row['name'] . ' ' . $row['surname']) ?></div>
                                            </div>
                                        </td>
                                        <td class="text-secondary"><?= htmlspecialchars($row['username']) ?></td>
                                        <td class="text-secondary"><?= isset($row['created_at']) ? date("d/m/Y", strtotime($row['created_at'])) : "-" ?></td>
                                        <td>
                                            <?php if($row['status'] == 'active'): ?>
                                                <span class="badge-status bg-active">Active</span>
                                            <?php else: ?>
                                                <span class="badge-status bg-inactive">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="manage_users.php?edit=<?= $row['id'] ?>" class="btn btn-sm btn-light border rounded-pill"><i class="fas fa-pen text-secondary"></i></a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-5 text-secondary">ยังไม่มีข้อมูลสมาชิก</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const ctx = document.getElementById('cropChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($crop_labels) ?>,
                datasets: [{
                    label: 'จำนวนรายการ',
                    data: <?= json_encode($crop_data) ?>,
                    backgroundColor: <?= json_encode($crop_colors) ?>,
                    borderWidth: 0,
                    borderRadius: 6,
                    barThickness: 50
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f3f4f6', borderDash: [5, 5] }, ticks: { color: '#6b7280' } },
                    x: { grid: { display: false }, ticks: { color: '#4b5563', font: { weight: '500' } } }
                }
            }
        });
    </script>
</body>
</html>