<?php
session_start();
include "./db.php";

// 1. Security Check
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// --- ฟังก์ชันป้องกัน Error เวลาส่งข้อมูลเข้า JS ---
function h_json($data) {
    return htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8');
}

// Admin Info (จำเป็นสำหรับ sidebar.php)
$current_id = $_SESSION['userid'];
$stmt_admin = $conn->prepare("SELECT name, surname FROM users WHERE id = ?");
$stmt_admin->bind_param("i", $current_id);
$stmt_admin->execute();
$current_admin = $stmt_admin->get_result()->fetch_assoc();
$admin_fullname = ($current_admin['name'] ?? 'Admin') . ' ' . ($current_admin['surname'] ?? '');

$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$filter_crop = isset($_GET['crop']) ? $_GET['crop'] : "";
$msg = "";

// --- 2. Logic: Update Data ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_log'])) {
    $id = $_POST['edit_id'];
    $date = $_POST['activity_date'];
    $activity = $_POST['activity_name'];
    $crop_type = $_POST['crop_type'];
    $variety = $_POST['crop_variety'];
    
    // คำนวณต้นทุน
    $cost_fert = floatval($_POST['cost_fertilizer']);
    $cost_chem = floatval($_POST['cost_chemical']);
    $cost_labor = floatval($_POST['cost_labor']);
    $total_cost = $cost_fert + $cost_chem + $cost_labor;

    $harvest_qty = floatval($_POST['harvest_qty']);
    $harvest_rev = floatval($_POST['harvest_revenue']);

    $stmt = $conn->prepare("UPDATE agricultural_logs SET 
        activity_date=?, activity_name=?, crop_type=?, crop_variety=?, 
        cost_fertilizer=?, cost_chemical=?, cost_labor=?, total_cost=?, 
        harvest_qty=?, harvest_revenue=? 
        WHERE id=?");
    
    $stmt->bind_param("ssssddddddi", $date, $activity, $crop_type, $variety, 
        $cost_fert, $cost_chem, $cost_labor, $total_cost, 
        $harvest_qty, $harvest_rev, $id);

    if ($stmt->execute()) {
        $msg = "<div class='alert alert-success shadow-sm border-0 rounded-3 mb-4 d-flex align-items-center'>
                    <i class='fas fa-check-circle me-3'></i>
                    <div>แก้ไขข้อมูล ID: <strong>$id</strong> สำเร็จแล้ว</div>
                    <button type='button' class='btn-close ms-auto' data-bs-dismiss='alert'></button>
                </div>";
    }
}

// 3. Logic: Delete
if (isset($_POST['delete_id'])) {
    $del_id = $_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM agricultural_logs WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        $msg = "<div class='alert alert-dark shadow-sm border-0 rounded-3 mb-4 d-flex align-items-center'>
                    <i class='fas fa-trash-alt me-3'></i>
                    <div>ลบข้อมูล ID: <strong>$del_id</strong> เรียบร้อยแล้ว</div>
                    <button type='button' class='btn-close ms-auto' data-bs-dismiss='alert'></button>
                </div>";
    }
}

// 4. Logic: Fetch Data
$sql = "SELECT logs.*, users.name 
        FROM agricultural_logs AS logs 
        LEFT JOIN users ON logs.user_id = users.id 
        WHERE (users.name LIKE ? OR logs.activity_name LIKE ? OR logs.crop_variety LIKE ?) ";

$params = ["sss"];
$search_param = "%{$search}%";
$bind_values = [$search_param, $search_param, $search_param];

if (!empty($filter_crop)) {
    $sql .= " AND logs.crop_type = ?";
    $params[0] .= "s";
    $bind_values[] = $filter_crop;
}

$sql .= " ORDER BY logs.activity_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($params[0], ...$bind_values);
$stmt->execute();
$result = $stmt->get_result();

$sql_stats = "SELECT COUNT(*) as total, SUM(total_cost) as cost, SUM(harvest_revenue) as rev FROM agricultural_logs";
$stats = $conn->query($sql_stats)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>รายการการผลิตทั้งหมด</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        /* CSS Theme หลัก (ต้องมีเพื่อให้ sidebar.php แสดงผลถูกต้อง) */
        :root {
            --bg-body: #f3f4f6; --card-bg: #ffffff; --text-primary: #1f2937; --text-secondary: #6b7280;
            --border-color: #e5e7eb; --agri-green: #588157; --agri-dark: #3a5a40;
            --sidebar-width: 260px; --sidebar-bg: #2b3035;
        }
        body { font-family: 'Kanit', sans-serif; background-color: var(--bg-body); color: var(--text-primary); padding-bottom: 80px; }

        /* Sidebar & Layout */
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
        .modal-card-header { position: relative; padding: 25px 25px 50px 25px; border-radius: 16px 16px 0 0; overflow: hidden; color: white; background: #6c757d; }
        .modal-card-header::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(45deg, rgba(0,0,0,0.2), transparent); }
        .modal-card-body { margin-top: -40px; padding: 0 20px 20px 20px; position: relative; z-index: 2; }
        
        .info-box { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 15px; border: 1px solid #edf2f7; }
        .info-label { font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; display: block; }
        .info-value { font-size: 1rem; font-weight: 500; color: var(--text-primary); }
        
        .cost-list-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #e2e8f0; font-size: 0.95rem; }
        .cost-list-item:last-child { border-bottom: none; }
        
        .theme-rice { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .theme-longan { background: linear-gradient(135deg, #d97706 0%, #b45309 100%); }
        .theme-rubber { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
        
        .icon-box-lg { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; background: rgba(255,255,255,0.2); color: white; backdrop-filter: blur(5px); }
        
        /* Table & Badge */
        .badge-status { padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
        .badge-success-soft { background: #d1fae5; color: #065f46; }
        .badge-gray-soft { background: #f3f4f6; color: #4b5563; }
        .btn-action { width: 32px; height: 32px; padding: 0; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; }
        .btn-action:hover { transform: translateY(-2px); }

        /* Mobile Card */
        .mobile-card { background: white; padding: 15px; border-radius: 16px; margin-bottom: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.03); border: 1px solid #f0f0f0; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <?php include 'mobile_menu.php'; ?>

    <main class="main-content">
        <div class="container-fluid p-0">
            <?= $msg ?>

            <div class="bg-white p-3 rounded-4 shadow-sm border mb-4">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-12 col-md-5">
                        <input type="text" name="search" class="form-control border-0 bg-light" placeholder="🔍 ค้นหา (ชื่อ, กิจกรรม, พันธุ์)..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <select name="crop" class="form-select border-0 bg-light" onchange="this.form.submit()">
                            <option value="">ทั้งหมด</option>
                            <option value="rice" <?= $filter_crop == 'rice' ? 'selected' : '' ?>>🌾 ข้าว</option>
                            <option value="longan" <?= $filter_crop == 'longan' ? 'selected' : '' ?>>🌳 ลำไย</option>
                            <option value="rubber" <?= $filter_crop == 'rubber' ? 'selected' : '' ?>>💧 ยางพารา</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-4 text-end">
                         <a href="view_all_production.php" class="btn btn-light w-100 w-md-auto text-secondary"><i class="fas fa-sync-alt"></i> Reset</a>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-4 shadow-sm border overflow-hidden d-none d-lg-block">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-4">Date / ID</th>
                                <th>User</th>
                                <th>Crop Info</th>
                                <th>Status</th>
                                <th class="text-end">Cost</th>
                                <th class="text-center pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): $result->data_seek(0); while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= date('d/m/Y', strtotime($row['activity_date'])) ?></div>
                                        <small class="text-muted">#<?= $row['id'] ?></small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:32px;height:32px;font-size:0.8rem;">
                                                <?= strtoupper(mb_substr($row['name'] ?? 'U', 0, 1)) ?>
                                            </div>
                                            <span><?= htmlspecialchars($row['name']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= $row['crop_type'] ?></span>
                                        <small class="text-secondary d-block mt-1"><?= $row['crop_variety'] ?></small>
                                    </td>
                                    <td>
                                        <?php if($row['harvest_revenue'] > 0): ?>
                                            <span class="badge-status badge-success-soft"><i class="fas fa-check-circle me-1"></i>เก็บเกี่ยวแล้ว</span>
                                        <?php else: ?>
                                            <span class="badge-status badge-gray-soft"><i class="fas fa-clock me-1"></i>กำลังปลูก</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end font-monospace"><?= number_format($row['total_cost']) ?></td>
                                    <td class="text-center pe-4">
                                        <button class="btn btn-action btn-light text-secondary border" onclick='viewFullDetails(<?= h_json($row) ?>)'><i class="fas fa-eye"></i></button>
                                        <button class="btn btn-action btn-light text-warning border" onclick='openEditModal(<?= h_json($row) ?>)'><i class="fas fa-pen"></i></button>
                                        <button class="btn btn-action btn-light text-danger border" onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name']) ?>')"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">ไม่พบข้อมูล</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-lg-none">
                <?php if ($result->num_rows > 0): $result->data_seek(0); while ($row = $result->fetch_assoc()): ?>
                    <div class="mobile-card">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center" style="width:30px;height:30px;font-size:0.8rem;">
                                    <?= strtoupper(mb_substr($row['name'] ?? 'U', 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="fw-bold" style="font-size:0.9rem;"><?= htmlspecialchars($row['name']) ?></div>
                                    <div class="text-muted" style="font-size:0.7rem;"><?= date('d/m/Y', strtotime($row['activity_date'])) ?></div>
                                </div>
                            </div>
                            <span class="badge bg-light text-dark border align-self-start"><?= $row['crop_type'] ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center border-top border-bottom py-2 my-2">
                            <div style="font-size:0.9rem;"><?= $row['activity_name'] ?></div>
                            <div class="text-danger fw-bold font-monospace"><?= number_format($row['total_cost']) ?> ฿</div>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button class="btn btn-sm btn-light border" onclick='viewFullDetails(<?= h_json($row) ?>)'><i class="fas fa-eye text-secondary"></i></button>
                            <button class="btn btn-sm btn-light border" onclick='openEditModal(<?= h_json($row) ?>)'><i class="fas fa-pen text-warning"></i></button>
                            <button class="btn btn-sm btn-light border" onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name']) ?>')"><i class="fas fa-trash text-danger"></i></button>
                        </div>
                    </div>
                <?php endwhile; endif; ?>
            </div>
        </div>
    </main>

    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 overflow-hidden shadow-lg">
                <div id="modal_header_bg" class="modal-card-header theme-rice">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-box-lg">
                                <i class="fas fa-seedling" id="v_icon_lg"></i>
                            </div>
                            <div>
                                <h4 class="fw-bold mb-0" id="v_activity_main">Activity Name</h4>
                                <div class="opacity-75 small mt-1">
                                    <i class="far fa-calendar me-1"></i> <span id="v_date_main"></span>
                                    <span class="mx-1">|</span>
                                    <i class="far fa-user me-1"></i> <span id="v_user_main"></span>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>

                <div class="modal-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-box h-100">
                                <h6 class="text-primary fw-bold mb-3 border-bottom pb-2"><i class="fas fa-leaf me-2"></i>ข้อมูลแปลงเกษตร</h6>
                                
                                <div class="row g-3">
                                    <div class="col-6">
                                        <span class="info-label">ประเภทพืช</span>
                                        <div class="info-value" id="v_crop_type_th">-</div>
                                    </div>
                                    <div class="col-6">
                                        <span class="info-label">สายพันธุ์</span>
                                        <div class="info-value" id="v_variety">-</div>
                                    </div>
                                    <div class="col-12">
                                        <span class="info-label">ขนาดพื้นที่ / จำนวน</span>
                                        <div class="info-value fw-bold text-dark fs-5" id="v_amount_display">-</div>
                                        <small class="text-muted" id="v_planting_method_display">วิธีการ: -</small>
                                    </div>
                                    <div class="col-12 pt-2">
                                        <span class="info-label">รหัสบันทึก (ID)</span>
                                        <span class="badge bg-secondary font-monospace" id="v_id_display">#0</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-box">
                                <h6 class="text-danger fw-bold mb-3 border-bottom pb-2"><i class="fas fa-file-invoice-dollar me-2"></i>รายละเอียดต้นทุน</h6>
                                <div class="cost-list-item">
                                    <span><i class="fas fa-flask text-muted me-2" style="width:20px;"></i>ปุ๋ย</span>
                                    <span class="font-monospace" id="v_cost_fert">0.00</span>
                                </div>
                                <div class="cost-list-item">
                                    <span><i class="fas fa-spray-can text-muted me-2" style="width:20px;"></i>ยา/เคมี</span>
                                    <span class="font-monospace" id="v_cost_chem">0.00</span>
                                </div>
                                <div class="cost-list-item">
                                    <span><i class="fas fa-users text-muted me-2" style="width:20px;"></i>แรงงาน</span>
                                    <span class="font-monospace" id="v_cost_labor">0.00</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                    <span class="fw-bold text-secondary">รวมต้นทุน</span>
                                    <span class="fw-bold text-danger fs-5 font-monospace" id="v_total_cost">0.00 ฿</span>
                                </div>
                            </div>

                            <div class="info-box bg-success bg-opacity-10 border-success border-opacity-25" id="v_harvest_box">
                                <h6 class="text-success fw-bold mb-2"><i class="fas fa-chart-line me-2"></i>สรุปผลผลิต</h6>
                                <div class="d-flex justify-content-between mb-1">
                                    <small>ปริมาณที่ได้</small>
                                    <span class="fw-bold text-dark" id="v_harvest_qty">-</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <small>รายได้จากการขาย</small>
                                    <span class="fw-bold text-success" id="v_revenue">-</span>
                                </div>
                                <div class="bg-white rounded p-2 text-center border border-success border-opacity-25">
                                    <small class="d-block text-muted" style="font-size:0.7rem;">กำไรสุทธิ (Net Profit)</small>
                                    <span class="fw-bold fs-4" id="v_net_profit">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-warning bg-opacity-10 border-bottom-0">
                    <h5 class="modal-title fw-bold text-dark">แก้ไขข้อมูล</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="edit_id" id="edit_id">
                        <input type="hidden" name="update_log" value="1">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6"><label class="form-label-sm">วันที่</label><input type="datetime-local" name="activity_date" id="edit_date" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label-sm">กิจกรรม</label><input type="text" name="activity_name" id="edit_activity" class="form-control" required></div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label-sm">พืช</label>
                                <select name="crop_type" id="edit_crop" class="form-select">
                                    <option value="rice">ข้าว</option>
                                    <option value="longan">ลำไย</option>
                                    <option value="rubber">ยางพารา</option>
                                </select>
                            </div>
                            <div class="col-6"><label class="form-label-sm">พันธุ์</label><input type="text" name="crop_variety" id="edit_variety" class="form-control"></div>
                        </div>
                        <h6 class="text-secondary fw-bold small mt-4 mb-2">แก้ไขต้นทุน</h6>
                        <div class="row g-2 bg-light p-3 rounded-3 border">
                            <div class="col-4"><label class="form-label-sm">ปุ๋ย</label><input type="number" step="0.01" name="cost_fertilizer" id="edit_cost_fert" class="form-control"></div>
                            <div class="col-4"><label class="form-label-sm">เคมี</label><input type="number" step="0.01" name="cost_chemical" id="edit_cost_chem" class="form-control"></div>
                            <div class="col-4"><label class="form-label-sm">แรงงาน</label><input type="number" step="0.01" name="cost_labor" id="edit_cost_labor" class="form-control"></div>
                        </div>
                        <h6 class="text-secondary fw-bold small mt-4 mb-2">ข้อมูลผลผลิต (ถ้ามี)</h6>
                        <div class="row g-2">
                            <div class="col-6"><label class="form-label-sm">จำนวน (กก.)</label><input type="number" step="0.01" name="harvest_qty" id="edit_harvest_qty" class="form-control"></div>
                            <div class="col-6"><label class="form-label-sm">รายได้ (บาท)</label><input type="number" step="0.01" name="harvest_revenue" id="edit_harvest_rev" class="form-control"></div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg text-center p-4 rounded-4">
                <div class="mb-3 text-danger"><i class="fas fa-trash-alt fa-3x"></i></div>
                <h5 class="fw-bold mb-2">ลบข้อมูล?</h5>
                <p class="text-secondary small mb-4">ข้อมูลของ: <strong id="del_user_name"></strong></p>
                <form method="POST" class="d-flex gap-2 justify-content-center">
                    <input type="hidden" name="delete_id" id="del_id_input">
                    <button type="button" class="btn btn-light w-100 rounded-pill" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger w-100 rounded-pill">ลบ</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const fmt = new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        function viewFullDetails(data) {
            const modalEl = document.getElementById('detailModal');
            const modal = new bootstrap.Modal(modalEl);
            
            // 1. Header Data
            document.getElementById('v_activity_main').innerText = data.activity_name;
            document.getElementById('v_date_main').innerText = new Date(data.activity_date).toLocaleDateString('th-TH', {year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit'});
            document.getElementById('v_user_main').innerText = data.name;
            document.getElementById('v_id_display').innerText = '#' + data.id;

            // 2. Theme & Crop Logic
            const headerBg = document.getElementById('modal_header_bg');
            const iconLg = document.getElementById('v_icon_lg');
            
            // Reset Classes
            headerBg.className = 'modal-card-header'; 
            
            let cropLabel = "พืชทั่วไป";
            let amountText = "-";
            let methodText = data.planting_method || '-';

            if(data.crop_type === 'rice') {
                headerBg.classList.add('theme-rice');
                iconLg.className = 'fas fa-seedling';
                cropLabel = "นาข้าว";
                let rai = data.area_rai || 0;
                let ngan = data.area_ngan || 0;
                let wah = data.area_wah || 0;
                amountText = `${rai} ไร่ ${ngan} งาน ${wah} วา`;
            } 
            else if(data.crop_type === 'longan') {
                headerBg.classList.add('theme-longan');
                iconLg.className = 'fas fa-tree';
                cropLabel = "สวนลำไย";
                amountText = (data.tree_amount ? fmt.format(data.tree_amount) : '0') + " ต้น";
                methodText = "ไม้ยืนต้น";
            } 
            else if(data.crop_type === 'rubber') {
                headerBg.classList.add('theme-rubber');
                iconLg.className = 'fas fa-tint'; // หยดน้ำยาง
                cropLabel = "สวนยางพารา";
                amountText = (data.tree_amount ? fmt.format(data.tree_amount) : '0') + " ต้น";
                methodText = "ไม้ยืนต้น";
            }

            document.getElementById('v_crop_type_th').innerText = cropLabel;
            document.getElementById('v_variety').innerText = data.crop_variety || '-';
            document.getElementById('v_amount_display').innerText = amountText;
            document.getElementById('v_planting_method_display').innerText = "วิธีการ: " + methodText;

            // 3. Financials
            document.getElementById('v_cost_fert').innerText = fmt.format(data.cost_fertilizer);
            document.getElementById('v_cost_chem').innerText = fmt.format(data.cost_chemical);
            document.getElementById('v_cost_labor').innerText = fmt.format(data.cost_labor);
            document.getElementById('v_total_cost').innerText = fmt.format(data.total_cost) + ' ฿';

            // 4. Harvest & Profit
            const rev = parseFloat(data.harvest_revenue) || 0;
            const cost = parseFloat(data.total_cost) || 0;
            const profit = rev - cost;
            const harvestBox = document.getElementById('v_harvest_box');

            if (rev > 0 || parseFloat(data.harvest_qty) > 0) {
                harvestBox.classList.remove('d-none');
                document.getElementById('v_harvest_qty').innerText = (parseFloat(data.harvest_qty) > 0 ? fmt.format(data.harvest_qty) : '0') + " กก.";
                document.getElementById('v_revenue').innerText = fmt.format(rev) + " ฿";
                
                const profitEl = document.getElementById('v_net_profit');
                profitEl.innerText = (profit > 0 ? '+' : '') + fmt.format(profit) + " ฿";
                profitEl.className = profit >= 0 ? "fw-bold fs-4 text-success" : "fw-bold fs-4 text-danger";
            } else {
                harvestBox.classList.add('d-none');
            }

            modal.show();
        }

        function openEditModal(data) {
            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            document.getElementById('edit_id').value = data.id;
            let dt = new Date(data.activity_date);
            dt.setMinutes(dt.getMinutes() - dt.getTimezoneOffset());
            document.getElementById('edit_date').value = dt.toISOString().slice(0,16);
            document.getElementById('edit_activity').value = data.activity_name;
            document.getElementById('edit_crop').value = data.crop_type;
            document.getElementById('edit_variety').value = data.crop_variety;
            document.getElementById('edit_cost_fert').value = data.cost_fertilizer;
            document.getElementById('edit_cost_chem').value = data.cost_chemical;
            document.getElementById('edit_cost_labor').value = data.cost_labor;
            document.getElementById('edit_harvest_qty').value = data.harvest_qty;
            document.getElementById('edit_harvest_rev').value = data.harvest_revenue;
            modal.show();
        }

        function confirmDelete(id, name) {
            document.getElementById('del_id_input').value = id;
            document.getElementById('del_user_name').innerText = name; 
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html>