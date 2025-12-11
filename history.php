<?php
session_start();
include "./db.php";

if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit;
}

// 1. สร้าง CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['userid'];
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$msg = "";

// --- ฟังก์ชันป้องกัน XSS สำหรับ JSON ---
function h_json($data) {
    return htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8');
}

// --- ฟังก์ชันแปลงวันที่ไทย ---
function thai_date_short($strDate) {
    if (!$strDate) return "-";
    $year = date("y", strtotime($strDate)) + 43;
    $month = date("n", strtotime($strDate));
    $day = date("j", strtotime($strDate));
    $thai_months = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
    return "$day " . $thai_months[$month] . " $year";
}

// 2. ลบข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security Warning: CSRF Token Mismatch");
    }
    $del_id = $_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM agricultural_logs WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $del_id, $user_id);
    if ($stmt->execute()) {
        $msg = "<div class='position-fixed top-0 start-50 translate-middle-x mt-3 z-3 alert alert-success rounded-pill shadow-sm px-4 fade show'><i class='fas fa-check-circle me-2'></i>ลบข้อมูลสำเร็จ</div>";
    }
}

// 3. บันทึกผลผลิต
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_harvest'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security Warning: CSRF Token Mismatch");
    }
    $log_id = $_POST['log_id'];
    $harvest_qty = floatval($_POST['harvest_qty']);
    $price_per_kg = isset($_POST['price_per_kg']) ? floatval($_POST['price_per_kg']) : 0;
    $harvest_revenue = $harvest_qty * $price_per_kg;

    $stmt = $conn->prepare("UPDATE agricultural_logs SET harvest_qty = ?, harvest_revenue = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ddii", $harvest_qty, $harvest_revenue, $log_id, $user_id);

    if ($stmt->execute()) {
        $msg = "<div class='position-fixed top-0 start-50 translate-middle-x mt-3 z-3 alert alert-success rounded-pill shadow-sm px-4 fade show'><i class='fas fa-coins me-2'></i>บันทึกรายได้สำเร็จ! (" . number_format($harvest_revenue) . " บ.)</div>";
    }
}

// 4. ดึงข้อมูล
$sql = "SELECT * FROM agricultural_logs WHERE user_id = ? 
        AND (activity_name LIKE ? OR crop_variety LIKE ? OR crop_type LIKE ?) 
        ORDER BY activity_date DESC";
$search_param = "%{$search}%";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isss", $user_id, $search_param, $search_param, $search_param);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ประวัติการเกษตร</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        :root { --primary: #3A5A40; --primary-dark: #344E41; --secondary: #588157; --accent: #A3B18A; --bg: #F3F6F4; --sidebar-width: 250px; }
        body { font-family: 'Sarabun', sans-serif; background-color: var(--bg); padding-bottom: 90px; }
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

        /* --- Search & Cards --- */
        .search-container { margin-top: 30px; padding: 0 20px; position: relative; z-index: 1021; }
        .search-input { border: none; border-radius: 50px; padding: 12px 20px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); width: 100%; font-family: 'Kanit'; }
        .search-btn { position: absolute; right: 25px; top: 50%; transform: translateY(-50%); border: none; background: none; color: var(--primary); }
        
        .history-card {
            background: white; border: none; border-radius: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03); transition: transform 0.2s, box-shadow 0.2s;
            position: relative; overflow: hidden; height: 100%;
        }
        @media (min-width: 992px) { .history-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05); } }
        .history-card:active { transform: scale(0.98); }

        .card-left-border { width: 6px; position: absolute; left: 0; top: 0; bottom: 0; }
        .border-rice { background: #F9A825; }
        .border-longan { background: #2E7D32; }
        .border-rubber { background: #1565C0; }

        .card-icon { width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-right: 15px; flex-shrink: 0; }
        .bg-icon-rice { background: #FFF8E1; color: #F9A825; }
        .bg-icon-longan { background: #E8F5E9; color: #2E7D32; }
        .bg-icon-rubber { background: #E3F2FD; color: #1565C0; }

        /* --- Modal & Details --- */
        .modal-header-custom { padding: 30px 20px; border-radius: 20px 20px 0 0; color: white; text-align: center; }
        .bg-rice-gradient { background: linear-gradient(135deg, #FBC02D 0%, #F57F17 100%); }
        .bg-longan-gradient { background: linear-gradient(135deg, #66BB6A 0%, #2E7D32 100%); }
        .bg-rubber-gradient { background: linear-gradient(135deg, #42A5F5 0%, #1565C0 100%); }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #eee; font-size: 0.95rem; }
        .detail-row:last-child { border-bottom: none; }

        /* --- Mobile Nav --- */
        .bottom-nav { position: fixed; bottom: 0; width: 100%; background: white; z-index: 1000; display: flex; justify-content: space-around; padding: 10px 0 20px; box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.05); border-radius: 25px 25px 0 0; }
        .nav-item-m { text-align: center; color: #bbb; text-decoration: none; font-size: 0.7rem; width: 60px; transition: 0.3s; }
        .nav-item-m i { font-size: 1.4rem; display: block; margin-bottom: 4px; }
        .nav-item-m.active { color: var(--primary); font-weight: 600; }
        
        .fab-center { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); width: 65px; height: 65px; background-color: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.1); border: 5px solid #f3f6f4; z-index: 1050; text-decoration: none; transition: transform 0.2s; }
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
            <a href="history.php" class="nav-link-custom active">
                <i class="fas fa-history"></i> ประวัติ
            </a>
            <a href="profile.php" class="nav-link-custom">
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
                        <i class="fas fa-history"></i>
                    </div>
                    <div>
                        <div class="font-head fw-bold lh-1" style="font-size: 1.1rem;">ประวัติการปลูก</div>
                        <div style="font-size: 0.75rem; opacity: 0.8;">รายการทั้งหมด</div>
                    </div>
                </div>

                <div class="d-none d-lg-block">
                    <h4 class="font-head fw-bold mb-0 text-dark">ประวัติการปลูก</h4>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <a href="savedata.php" class="btn btn-success fw-bold d-none d-lg-block rounded-pill px-4 shadow-sm">
                    <i class="fas fa-plus me-2"></i>เพิ่มข้อมูล
                </a>

                <div class="dropdown">
                    <a href="#" data-bs-toggle="dropdown" class="d-flex align-items-center text-decoration-none gap-2">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['fullname']) ?>&background=random&color=<?= isset($_SESSION['userid'])?'fff':'333' ?>" class="user-avatar shadow-sm">
                        <span class="font-head d-none d-xl-block" style="color: inherit;"><?= $_SESSION['fullname'] ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 mt-2">
                        <li><a class="dropdown-item small" href="profile.php"><i class="fas fa-user me-2"></i>โปรไฟล์</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item small text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="d-lg-none" style="height: 20px; background: var(--primary);"></div>

        <div class="container search-container">
            <form action="" method="GET">
                <div class="position-relative">
                    <input type="text" name="search" class="search-input" placeholder="ค้นหา พืช, กิจกรรม..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>

        <div class="container pt-4">
            <div class="row g-3">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        $type = $row['crop_type'];
                        $is_locked = ($row['harvest_revenue'] > 0 || $row['harvest_qty'] > 0);
                        $profit = $row['harvest_revenue'] - $row['total_cost'];

                        $borderClass = ($type == 'rice') ? 'border-rice' : (($type == 'longan') ? 'border-longan' : 'border-rubber');
                        $iconClass = ($type == 'rice') ? 'bg-icon-rice' : (($type == 'longan') ? 'bg-icon-longan' : 'bg-icon-rubber');
                        $icon = ($type == 'rice') ? 'fa-seedling' : (($type == 'longan') ? 'fa-apple-alt' : 'fa-tree');
                        ?>

                        <div class="col-md-6 col-lg-4">
                            <div class="card history-card p-3">
                                <div class="card-left-border <?= $borderClass ?>"></div>

                                <div class="d-flex justify-content-between align-items-start mb-2 ps-2">
                                    <div class="d-flex align-items-center">
                                        <div class="card-icon <?= $iconClass ?>">
                                            <i class="fas <?= $icon ?>"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold mb-0 font-head text-dark"><?= htmlspecialchars($row['crop_variety']) ?></h6>
                                            <small class="text-muted" style="font-size: 0.75rem;">
                                                <?= thai_date_short($row['activity_date']) ?> •
                                                <?= htmlspecialchars($row['activity_name']) ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light rounded-circle" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v text-muted"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-4">
                                            <li><a class="dropdown-item" href="#" onclick='viewDetails(<?= h_json($row) ?>)'><i class="fas fa-eye me-2 text-primary"></i>รายละเอียด</a></li>
                                            <?php if (!$is_locked): ?>
                                                <li><a class="dropdown-item" href="savedata.php?edit_id=<?= $row['id'] ?>"><i class="fas fa-edit me-2 text-warning"></i>แก้ไข</a></li>
                                            <?php endif; ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="POST" onsubmit="return confirm('ยืนยันลบรายการนี้?');" style="margin:0;">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                    <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                                    <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash me-2"></i>ลบรายการ</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="row g-2 mb-3 ps-2">
                                    <div class="col-6">
                                        <div class="bg-light p-2 rounded-3 text-center border border-light">
                                            <small class="text-muted d-block" style="font-size: 0.7rem;">ต้นทุน</small>
                                            <span class="text-danger fw-bold font-head"><?= number_format($row['total_cost']) ?></span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="bg-light p-2 rounded-3 text-center border border-light">
                                            <small class="text-muted d-block" style="font-size: 0.7rem;">รายได้</small>
                                            <?php if ($is_locked): ?>
                                                <span class="text-success fw-bold font-head"><?= number_format($row['harvest_revenue']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="ps-2 mt-auto">
                                    <?php if (!$is_locked): ?>
                                        <button class="btn btn-warning text-dark w-100 rounded-pill fw-bold shadow-sm"
                                            onclick='openHarvestModal(<?= h_json($row) ?>)'
                                            style="background: #FFD54F; border: none;">
                                            <i class="fas fa-hand-holding-usd me-2"></i>บันทึกผลผลิต
                                        </button>
                                    <?php else: ?>
                                        <div class="d-flex justify-content-between align-items-center bg-success bg-opacity-10 p-2 rounded-pill px-3 border border-success border-opacity-25">
                                            <small class="text-success fw-bold"><i class="fas fa-chart-line me-1"></i> กำไรสุทธิ</small>
                                            <span class="<?= $profit >= 0 ? 'text-success' : 'text-danger' ?> fw-bold font-head">
                                                <?= ($profit >= 0 ? '+' : '') . number_format($profit) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <div class="bg-white rounded-circle d-inline-flex p-4 shadow-sm mb-3">
                            <i class="fas fa-search fa-2x text-muted opacity-50"></i>
                        </div>
                        <p class="text-muted">ไม่พบข้อมูลการเพาะปลูก</p>
                        <a href="savedata.php" class="btn btn-sm btn-success rounded-pill px-4">เพิ่มข้อมูลใหม่</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <a href="savedata.php" class="fab-center d-lg-none">
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
            <a href="history.php" class="nav-item-m active">
                <i class="fas fa-history"></i>ประวัติ
            </a>
            <a href="profile.php" class="nav-item-m">
                <i class="fas fa-user"></i>โปรไฟล์
            </a>
        </div>

        <div class="modal fade" id="harvestModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0">
                    <div class="modal-header bg-warning text-dark border-0">
                        <h5 class="modal-title font-head fw-bold"><i class="fas fa-coins me-2"></i>บันทึกรายได้</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="modal-body p-4">
                            <input type="hidden" name="log_id" id="h_log_id">

                            <div class="mb-4">
                                <label class="form-label text-muted small fw-bold">1. ปริมาณผลผลิต (กิโลกรัม)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-weight-hanging text-warning"></i></span>
                                    <input type="number" step="0.01" name="harvest_qty" id="inp_qty" class="form-control border-start-0" placeholder="0.00" required oninput="calcTotal()">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-muted small fw-bold">2. ราคาขายต่อหน่วย (บาท/กก.)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-tag text-warning"></i></span>
                                    <input type="number" step="0.01" name="price_per_kg" id="inp_price" class="form-control border-start-0" placeholder="0.00" required oninput="calcTotal()">
                                </div>
                            </div>

                            <div class="alert alert-warning bg-opacity-10 border-warning border-opacity-25 rounded-3 text-center">
                                <small class="d-block text-warning fw-bold mb-1">รายได้รวมโดยประมาณ</small>
                                <h3 class="fw-bold text-dark mb-0 font-head" id="preview_total">0.00</h3>
                                <small class="text-muted">บาท</small>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0 justify-content-center">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" name="save_harvest" class="btn btn-warning rounded-pill px-5 fw-bold shadow-sm">บันทึก</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="viewModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 overflow-hidden">
                    <div id="v_header_bg" class="modal-header-custom position-relative">
                        <h3 class="fw-bold mb-1 font-head" id="v_variety"></h3>
                        <span class="badge bg-white bg-opacity-25 rounded-pill px-3 py-1 fw-normal" id="v_date"></span>
                        <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 bg-light">
                        <div class="bg-white p-3 rounded-3 shadow-sm mb-3">
                            <h6 class="text-primary fw-bold font-head mb-3"><i class="fas fa-info-circle me-2"></i>ข้อมูลการปลูก</h6>
                            <div id="v_rice_info" style="display:none;">
                                <div class="detail-row"><span>ฤดูกาล</span><span class="fw-bold text-dark" id="v_season"></span></div>
                                <div class="detail-row"><span>วิธีปลูก</span><span class="fw-bold text-dark" id="v_method"></span></div>
                                <div class="detail-row"><span>พื้นที่</span><span class="fw-bold text-dark" id="v_area"></span></div>
                            </div>
                            <div id="v_tree_info" style="display:none;">
                                <div class="detail-row"><span>จำนวนต้น</span><span class="fw-bold text-dark" id="v_tree"></span></div>
                            </div>
                            <div class="detail-row"><span>บันทึกเพิ่มเติม</span><span class="text-muted" id="v_note"></span></div>
                        </div>

                        <div class="bg-white p-3 rounded-3 shadow-sm">
                            <h6 class="text-success fw-bold font-head mb-3"><i class="fas fa-wallet me-2"></i>สรุปยอดเงิน</h6>
                            <div class="detail-row"><span class="text-muted">ค่าปุ๋ย</span><span id="v_cost_fert"></span></div>
                            <div class="detail-row"><span class="text-muted">ค่ายา/เคมี</span><span id="v_cost_chem"></span></div>
                            <div class="detail-row"><span class="text-muted">ค่าแรงงาน</span><span id="v_cost_labor"></span></div>
                            <div class="detail-row border-top mt-2 pt-2 fw-bold text-danger">
                                <span>รวมต้นทุน</span><span id="v_total"></span>
                            </div>

                            <div id="v_revenue_section" style="display:none;">
                                <div class="detail-row mt-2 pt-2 border-top fw-bold text-success bg-success bg-opacity-10 p-2 rounded-2">
                                    <span>รายได้รวม</span><span id="v_rev_show"></span>
                                </div>
                                <div class="text-end small text-muted mt-1 fst-italic" id="v_price_detail"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // คำนวณรายได้ Real-time ใน Modal
        function calcTotal() {
            let qty = parseFloat(document.getElementById('inp_qty').value) || 0;
            let price = parseFloat(document.getElementById('inp_price').value) || 0;
            let total = qty * price;
            document.getElementById('preview_total').innerText = total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function openHarvestModal(data) {
            new bootstrap.Modal(document.getElementById('harvestModal')).show();
            document.getElementById('h_log_id').value = data.id;
            // Reset form
            document.getElementById('inp_qty').value = '';
            document.getElementById('inp_price').value = '';
            document.getElementById('preview_total').innerText = '0.00';
        }

        function viewDetails(data) {
            new bootstrap.Modal(document.getElementById('viewModal')).show();

            // Set Header Color
            const header = document.getElementById('v_header_bg');
            header.className = 'modal-header-custom position-relative ' + (data.crop_type == 'rice' ? 'bg-rice-gradient' : (data.crop_type == 'longan' ? 'bg-longan-gradient' : 'bg-rubber-gradient'));

            // Set Data
            document.getElementById('v_variety').innerText = data.crop_variety;
            // Simple date format for JS
            const d = new Date(data.activity_date);
            document.getElementById('v_date').innerText = d.getDate() + '/' + (d.getMonth() + 1) + '/' + (d.getFullYear() + 543);

            // Toggle Info Section
            if (data.crop_type == 'rice') {
                document.getElementById('v_rice_info').style.display = 'block';
                document.getElementById('v_tree_info').style.display = 'none';
                document.getElementById('v_season').innerText = (data.crop_season == 'in_season' ? 'นาปี' : 'นาปรัง');
                document.getElementById('v_method').innerText = (data.planting_method == 'sow' ? 'นาหว่าน' : 'นาดำ');
                document.getElementById('v_area').innerText = data.area_rai + ' ไร่ ' + data.area_ngan + ' งาน';
            } else {
                document.getElementById('v_rice_info').style.display = 'none';
                document.getElementById('v_tree_info').style.display = 'block';
                document.getElementById('v_tree').innerText = data.tree_amount + ' ต้น';
            }
            document.getElementById('v_note').innerText = data.note || '-';

            // Costs
            const fmt = new Intl.NumberFormat('th-TH');
            document.getElementById('v_cost_fert').innerText = fmt.format(data.cost_fertilizer);
            document.getElementById('v_cost_chem').innerText = fmt.format(data.cost_chemical);
            document.getElementById('v_cost_labor').innerText = fmt.format(data.cost_labor);
            document.getElementById('v_total').innerText = fmt.format(data.total_cost) + ' บาท';

            // Revenue Logic
            if (data.harvest_revenue > 0) {
                document.getElementById('v_revenue_section').style.display = 'block';
                document.getElementById('v_rev_show').innerText = fmt.format(data.harvest_revenue) + ' บาท';

                if (data.harvest_qty > 0) {
                    let pricePerKg = data.harvest_revenue / data.harvest_qty;
                    document.getElementById('v_price_detail').innerText = `(ขาย ${fmt.format(data.harvest_qty)} กก. × ${fmt.format(pricePerKg)} บาท/กก.)`;
                }
            } else {
                document.getElementById('v_revenue_section').style.display = 'none';
            }
        }

        // Auto hide alert
        setTimeout(function () {
            let alert = document.querySelector('.alert');
            if (alert) alert.classList.remove('show');
        }, 3000);
    </script>
</body>

</html>