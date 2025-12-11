<?php
session_start();
include "./db.php";

// ตรวจสอบการ Login
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['userid'];

// --- ฟังก์ชันแปลงวันที่เป็นภาษาไทย ---
function thai_date($strDate) {
    if (!$strDate) return "-";
    $year_be = date("Y", strtotime($strDate)) + 543;
    $month = date("m", strtotime($strDate));
    $day = date("d", strtotime($strDate));
    return "$day/$month/$year_be";
}

// --- ฟังก์ชันป้องกัน XSS ---
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// --- Initialize ---
$logs = []; 
$stats = ['total' => 0, 'total_cost' => 0, 'total_rev' => 0]; 
$crop_labels = []; 
$crop_data = [];
$db_error = ""; 

// --- 1. Query Data ---
$sql_all = "SELECT * FROM agricultural_logs WHERE user_id = ? ORDER BY activity_date DESC";
if ($stmt_all = $conn->prepare($sql_all)) {
    $stmt_all->bind_param("i", $user_id);
    if ($stmt_all->execute()) {
        $result_all = $stmt_all->get_result();
        while ($row = $result_all->fetch_assoc()) {
            $logs[] = $row;
        }
    }
    $stmt_all->close();
} else {
    $db_error = "ไม่สามารถดึงข้อมูลได้: " . $conn->error;
}

// --- 2. Statistics ---
$sql_stats = "SELECT COUNT(*) as total, SUM(total_cost) as total_cost, SUM(harvest_revenue) as total_rev FROM agricultural_logs WHERE user_id = ?";
if ($stmt_stats = $conn->prepare($sql_stats)) {
    $stmt_stats->bind_param("i", $user_id);
    $stmt_stats->execute();
    $res_stats = $stmt_stats->get_result();
    if ($res_stats) {
        $stats = $res_stats->fetch_assoc();
    }
    $stmt_stats->close();
}
$net_profit = ($stats['total_rev'] ?? 0) - ($stats['total_cost'] ?? 0);

// --- 3. Chart Data ---
$sql_crop = "SELECT crop_type, COUNT(*) as count FROM agricultural_logs WHERE user_id = ? GROUP BY crop_type";
if ($stmt_crop = $conn->prepare($sql_crop)) {
    $stmt_crop->bind_param("i", $user_id);
    $stmt_crop->execute();
    $res_crop = $stmt_crop->get_result();
    if ($res_crop) {
        while($row = $res_crop->fetch_assoc()) {
            $crop_labels[] = ($row['crop_type']=='rice'?'ข้าว':($row['crop_type']=='longan'?'ลำไย':'ยางพารา'));
            $crop_data[] = $row['count'];
        }
    }
    $stmt_crop->close();
}

// --- 4. Slider Data ---
$slider_available = false;
$sql_slider = "SELECT * FROM news_sliders ORDER BY id DESC";
$res_slider = $conn->query($sql_slider);
if ($res_slider && $res_slider->num_rows > 0) {
    $slider_available = true;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Smart Farm Dashboard</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root { --primary: #3A5A40; --primary-dark: #344E41; --bg: #F3F6F4; --accent: #A3B18A; --sidebar-width: 250px; }
        body { font-family: 'Sarabun', sans-serif; background-color: var(--bg); padding-bottom: 90px; }
        h1, h2, h3, h4, h5, .font-head { font-family: 'Kanit', sans-serif; }
        
        /* --- Sidebar Style (New) --- */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: var(--primary);
            color: white;
            z-index: 1040;
            display: flex;
            flex-direction: column;
            padding: 20px 15px;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
        }
        .sidebar-brand {
            font-family: 'Kanit';
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 40px;
            padding-left: 10px;
            color: #fff;
            text-decoration: none;
        }
        .nav-link-custom {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 8px;
            transition: 0.3s;
            font-family: 'Kanit';
            font-size: 1rem;
        }
        .nav-link-custom:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
            transform: translateX(5px);
        }
        .nav-link-custom.active {
            background: white;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .nav-link-custom i { width: 25px; text-align: center; margin-right: 10px; font-size: 1.1rem; }

        /* --- Wrapper for Main Content --- */
        .main-wrapper {
            transition: margin-left 0.3s;
        }

        /* --- Header Style Update --- */
        .app-header { 
            background-color: var(--primary); 
            color: white; 
            padding: 15px 20px; 
            position: sticky; 
            top: 0; 
            z-index: 1020; 
            box-shadow: 0 4px 15px rgba(58, 90, 64, 0.2); 
            border-bottom-left-radius: 20px; 
            border-bottom-right-radius: 20px; 
        }

        /* --- Desktop Layout Adjustments --- */
        @media (min-width: 992px) { 
            body { padding-bottom: 0; } 
            .app-header { 
                border-radius: 0; /* Header เต็มจอ ไม่ต้องมนมุมล่างเมื่อมี Sidebar */
                background-color: white; /* เปลี่ยน Header เป็นขาวบน PC เพื่อให้ตัดกับ Sidebar */
                color: #333; /* Text สีเข้ม */
                padding: 15px 40px;
            }
            .app-header .font-head { color: var(--primary); } /* หัวข้อให้กลับเป็นสีเขียว */
            .main-wrapper { margin-left: var(--sidebar-width); } /* ขยับเนื้อหาหนี Sidebar */
            .container { max-width: 1200px; padding-top: 20px; } 
        }

        .user-avatar { width: 40px; height: 40px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.8); object-fit: cover; }
        @media (min-width: 992px) { .user-avatar { border-color: #ddd; } } /* Border สีเทาเมื่ออยู่บนพื้นขาว */

        /* ... (Old Styles Keep As Is) ... */
        .news-card { border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); position: relative; border: none; background: #000; }
        .news-carousel .carousel-item::after { content: ""; position: absolute; bottom: 0; left: 0; width: 100%; height: 80%; background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.5) 40%, transparent 100%); z-index: 1; pointer-events: none; }
        .news-img { width: 100%; object-fit: cover; transition: transform 0.5s ease; height: 180px; } 
        @media (min-width: 768px) { .news-img { height: 240px; } } @media (min-width: 992px) { .news-img { height: 280px; } }
        .news-card:hover .news-img { transform: scale(1.03); }
        .news-caption { position: absolute; bottom: 15px; left: 20px; right: 20px; z-index: 2; text-align: left; }
        .news-title { font-family: 'Kanit', sans-serif; color: #fff; font-weight: 600; text-shadow: 0 2px 4px rgba(0,0,0,0.3); margin-bottom: 5px; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; font-size: 1rem; }
        @media (min-width: 768px) { .news-title { font-size: 1.4rem; -webkit-line-clamp: 2; } }
        .news-badge { background: var(--primary); color: white; padding: 3px 8px; border-radius: 20px; font-size: 0.7rem; display: inline-block; margin-bottom: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.3); }
        .carousel-indicators [data-bs-target] { background-color: #fff; opacity: 0.7; width: 6px; height: 6px; border-radius: 50%; margin: 0 3px; }
        .carousel-indicators .active { opacity: 1; background-color: var(--primary); width: 12px; border-radius: 10px; }
        .stat-card-mini { background: white; border-radius: 16px; padding: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); height: 100%; display: flex; flex-direction: column; justify-content: center; transition: transform 0.2s, box-shadow 0.2s; }
        @media (min-width: 992px) { .stat-card-mini:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); } }
        .stat-label { font-size: 0.75rem; color: #888; margin-bottom: 4px; }
        .stat-val { font-family: 'Kanit'; font-weight: 600; font-size: 1.1rem; }
        .chart-card { background: white; border-radius: 20px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); margin-bottom: 20px; position: relative; overflow: hidden; height: 100%; }
        .log-item { background: white; border-radius: 15px; padding: 15px; margin-bottom: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 15px; transition: transform 0.2s; }
        .log-item:active { transform: scale(0.98); }
        .log-icon { width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.2rem; }
        .btn-export-mini { border: none; padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; font-family: 'Kanit'; color: white; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s; }
        .btn-export-mini:hover { opacity: 0.9; }
        .bottom-nav { position: fixed; bottom: 0; width: 100%; background: white; z-index: 1000; display: flex; justify-content: space-around; padding: 10px 0 20px; box-shadow: 0 -5px 20px rgba(0,0,0,0.05); border-radius: 25px 25px 0 0; }
        .nav-item-m { text-align: center; color: #bbb; text-decoration: none; font-size: 0.7rem; width: 60px; transition: 0.3s; }
        .nav-item-m i { font-size: 1.4rem; display: block; margin-bottom: 4px; }
        .nav-item-m.active { color: var(--primary); font-weight: 600; }
        .fab-center { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); width: 65px; height: 65px; background-color: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 -4px 10px rgba(0,0,0,0.1); border: 5px solid #f3f6f4; z-index: 1050; text-decoration: none; transition: transform 0.2s; }
        .fab-center:active { transform: translateX(-50%) scale(0.95); }
        #exportTableContainer { display: none; }
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
            
            <a href="index.php" class="nav-link-custom active">
                <i class="fas fa-home"></i> หน้าหลัก
            </a>
            <a href="calendar.php" class="nav-link-custom">
                <i class="fas fa-calendar-alt"></i> ปฏิทิน
            </a>
            <a href="history.php" class="nav-link-custom">
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
        
        <header class="app-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-4">
                <div class="d-flex align-items-center gap-2 d-lg-none">
                    <div style="width: 35px; height: 35px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div>
                        <div class="font-head fw-bold lh-1" style="font-size: 1.1rem;">สมุดบันทึก</div>
                        <div style="font-size: 0.75rem; opacity: 0.8;">หมู่บ้านแม่ต๋ำต้นโพธิ์</div>
                    </div>
                </div>

                <div class="d-none d-lg-block">
                    <h4 class="font-head fw-bold mb-0 text-dark">Dashboard</h4>
                    <small class="text-muted">ยินดีต้อนรับกลับ, <?= h($_SESSION['fullname'] ?? 'User') ?></small>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <a href="savedata.php" class="btn btn-success fw-bold d-none d-lg-block rounded-pill px-4 shadow-sm">
                    <i class="fas fa-plus me-2"></i>บันทึกข้อมูล
                </a>

                <div class="dropdown">
                    <a href="#" data-bs-toggle="dropdown">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['fullname'] ?? 'User') ?>&background=random&color=fff" class="user-avatar shadow-sm">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 mt-2">
                        <li><a class="dropdown-item small" href="profile.php"><i class="fas fa-user me-2"></i>โปรไฟล์</a></li>
                        <li class="d-lg-none"><a class="dropdown-item small text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="container pt-4">

            <?php if(!empty($db_error)): ?>
                <div class="alert alert-danger shadow-sm border-0 rounded-3 mb-3">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= h($db_error) ?>
                </div>
            <?php endif; ?>

            <?php if ($slider_available): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card news-card"> 
                        <div id="newsCarousel" class="carousel slide news-carousel" data-bs-ride="carousel">
                            <div class="carousel-indicators">
                                <?php 
                                $res_slider->data_seek(0);
                                $slideIndex = 0;
                                while($slide = $res_slider->fetch_assoc()): 
                                ?>
                                    <button type="button" data-bs-target="#newsCarousel" data-bs-slide-to="<?= $slideIndex ?>" class="<?= $slideIndex === 0 ? 'active' : '' ?>" aria-current="true"></button>
                                <?php 
                                $slideIndex++;
                                endwhile; 
                                ?>
                            </div>
                            <div class="carousel-inner">
                                <?php 
                                $isActive = true;
                                $res_slider->data_seek(0); 
                                while($slide = $res_slider->fetch_assoc()): 
                                ?>
                                    <div class="carousel-item <?= $isActive ? 'active' : '' ?>">
                                        <a href="news_detail.php?id=<?= $slide['id'] ?>" class="text-decoration-none">
                                            <img src="<?= h($slide['image_path']) ?>" class="d-block news-img" alt="<?= h($slide['title']) ?>">
                                            <?php if($slide['title']): ?>
                                                <div class="news-caption">
                                                    <span class="news-badge"><i class="fas fa-bullhorn me-1"></i> ข่าวสาร</span>
                                                    <h5 class="news-title"><?= h($slide['title']) ?></h5>
                                                </div>
                                            <?php endif; ?>
                                        </a>
                                    </div>
                                <?php 
                                    $isActive = false; 
                                endwhile; 
                                ?>
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#newsCarousel" data-bs-slide="prev" style="z-index: 3;"><span class="carousel-control-prev-icon" aria-hidden="true"></span></button>
                            <button class="carousel-control-next" type="button" data-bs-target="#newsCarousel" data-bs-slide="next" style="z-index: 3;"><span class="carousel-control-next-icon" aria-hidden="true"></span></button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="row g-2 g-lg-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="stat-card-mini">
                        <div class="stat-label"><i class="fas fa-list me-1"></i> รายการ</div>
                        <div class="stat-val text-dark"><?= number_format($stats['total'] ?? 0) ?></div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card-mini">
                        <div class="stat-label"><i class="fas fa-coins me-1"></i> กำไรสุทธิ</div>
                        <div class="stat-val <?= $net_profit>=0?'text-success':'text-danger' ?>">
                            <?= number_format($net_profit) ?>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card-mini">
                        <div class="stat-label text-danger"><i class="fas fa-arrow-down me-1"></i> ต้นทุนรวม</div>
                        <div class="stat-val text-danger"><?= number_format($stats['total_cost'] ?? 0) ?></div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card-mini">
                        <div class="stat-label text-success"><i class="fas fa-arrow-up me-1"></i> รายได้รวม</div>
                        <div class="stat-val text-success"><?= number_format($stats['total_rev'] ?? 0) ?></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8 order-2 order-lg-1">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="font-head fw-bold mb-0 text-dark">ภาพรวมวันนี้</h5>
                        <div class="d-flex gap-1">
                            <button onclick="exportPDF()" class="btn-export-mini bg-danger"><i class="fas fa-file-pdf"></i><span class="d-none d-md-inline ms-1">PDF</span></button>
                            <button onclick="exportExcel()" class="btn-export-mini bg-success"><i class="fas fa-file-excel"></i><span class="d-none d-md-inline ms-1">Excel</span></button>
                            <button onclick="exportCSV()" class="btn-export-mini bg-primary"><i class="fas fa-file-csv"></i><span class="d-none d-md-inline ms-1">CSV</span></button>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="font-head fw-bold mb-0 text-muted small">รายการล่าสุด</h6>
                        <a href="history.php" class="text-decoration-none small text-muted">ดูทั้งหมด ></a>
                    </div>

                    <div class="mb-4">
                        <?php 
                            if(count($logs) > 0):
                                $count = 0;
                                foreach($logs as $row): 
                                    if($count >= 5) break; 
                                    $count++;
                                    $profit = ($row['harvest_revenue'] ?? 0) - ($row['total_cost'] ?? 0);
                                    
                                    $icon = 'fa-seedling'; $bg = '#fff8e1'; $txt = '#f57f17'; 
                                    if($row['crop_type']=='longan') { $icon='fa-apple-alt'; $bg='#e8f5e9'; $txt='#2e7d32'; }
                                    if($row['crop_type']=='rubber') { $icon='fa-tree'; $bg='#e3f2fd'; $txt='#1565c0'; }
                        ?>
                                <div class="log-item">
                                    <div class="log-icon" style="background: <?= $bg ?>; color: <?= $txt ?>;">
                                        <i class="fas <?= $icon ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold font-head text-dark"><?= h($row['crop_variety']) ?></div>
                                        <div style="font-size: 0.75rem; color: #aaa;">
                                            <?= thai_date($row['activity_date']) ?> • <?= h($row['activity_name']) ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold font-head <?= $profit>=0?'text-success':'text-danger' ?>">
                                            <?= number_format($profit) ?>
                                        </div>
                                        <small class="text-muted" style="font-size: 0.7rem;">บาท</small>
                                    </div>
                                </div>
                            <?php endforeach; else: ?>
                                <div class="text-center py-5 text-muted bg-white rounded-4 shadow-sm">
                                    <i class="fas fa-clipboard-list fa-3x mb-3 opacity-25"></i>
                                    <p class="mb-0">ยังไม่มีข้อมูล</p>
                                    <small>กดปุ่ม + เพื่อเริ่มจดบันทึก</small>
                                </div>
                            <?php endif; ?>
                    </div>
                </div>

                <?php if(($stats['total'] ?? 0) > 0): ?>
                <div class="col-lg-4 order-1 order-lg-2 mb-4">
                    <div class="chart-card d-flex flex-column justify-content-center">
                        <h6 class="font-head fw-bold mb-3 small text-muted">สัดส่วนพืชที่ปลูก</h6>
                        <div class="d-flex flex-row flex-lg-column align-items-center justify-content-between h-100">
                            <div style="width: 120px; height: 120px; margin-bottom: 1rem;">
                                <canvas id="miniChart"></canvas>
                            </div>
                            <div class="flex-grow-1 ps-3 ps-lg-0 w-100">
                                <?php 
                                $colors = ['#F9A825', '#2E7D32', '#1565C0']; 
                                foreach($crop_labels as $index => $label): 
                                    $val = $crop_data[$index];
                                    $percent = ($val / $stats['total']) * 100;
                                ?>
                                    <div class="d-flex align-items-center mb-2 small">
                                        <span style="width:10px; height:10px; background:<?=$colors[$index]?>; border-radius:50%; display:inline-block; margin-right:8px;"></span>
                                        <span class="text-muted flex-grow-1"><?= $label ?></span>
                                        <span class="fw-bold"><?= round($percent) ?>%</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div style="height: 40px;"></div> 
        </div> </div> <div id="exportTableContainer">
        <h3 style="text-align: center; font-family: 'Sarabun';">รายงานสรุปผลการเกษตร</h3>
        <p style="text-align: center;">ข้อมูล ณ วันที่: <?= thai_date(date('Y-m-d')) ?></p>
        <table id="fullExportTable" border="1" cellspacing="0" cellpadding="5" style="width: 100%; border-collapse: collapse; font-family: 'Sarabun'; font-size: 12px;">
            <thead style="background: #3A5A40; color: white;">
                <tr>
                    <th>วันที่</th><th>พืช</th><th>กิจกรรม</th><th>รายละเอียด</th><th>ต้นทุน</th><th>รายได้</th><th>กำไร</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach($logs as $row): 
                    $profit = ($row['harvest_revenue'] ?? 0) - ($row['total_cost'] ?? 0);
                ?>
                <tr>
                    <td><?= thai_date($row['activity_date']) ?></td>
                    <td><?= h($row['crop_type']) ?></td>
                    <td><?= h($row['activity_name']) ?></td>
                    <td><?= h($row['note']) ?></td>
                    <td align="right"><?= $row['total_cost'] ?></td>
                    <td align="right"><?= $row['harvest_revenue'] ?></td>
                    <td align="right"><?= $profit ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <a href="savedata.php" class="fab-center d-lg-none">
        <i class="fas fa-plus"></i>
    </a>

    <div class="bottom-nav d-lg-none">
        <a href="index.php" class="nav-item-m active">
            <i class="fas fa-home"></i>หน้าหลัก
        </a>
        <a href="calendar.php" class="nav-item-m">
            <i class="fas fa-calendar-alt"></i>ปฏิทิน
        </a>
        <div style="width: 60px;"></div> 
        <a href="history.php" class="nav-item-m">
            <i class="fas fa-history"></i>ประวัติ
        </a>
        <a href="profile.php" class="nav-item-m">
            <i class="fas fa-user"></i>โปรไฟล์
        </a>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const ctx = document.getElementById('miniChart');
        if(ctx) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode($crop_labels) ?>,
                    datasets: [{
                        data: <?= json_encode($crop_data) ?>,
                        backgroundColor: ['#F9A825', '#2E7D32', '#1565C0'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: { legend: { display: false } }
                }
            });
        }

        function exportExcel() {
            var wb = XLSX.utils.table_to_book(document.getElementById("fullExportTable"), {sheet: "FarmData"});
            XLSX.writeFile(wb, 'FarmReport.xlsx');
        }
        function exportCSV() {
            var wb = XLSX.utils.table_to_book(document.getElementById("fullExportTable"), {sheet: "Sheet1"});
            var csv = XLSX.utils.sheet_to_csv(wb.Sheets["Sheet1"]);
            var blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = "FarmReport.csv";
            link.click();
        }
        function exportPDF() {
            const element = document.getElementById('exportTableContainer');
            element.style.display = 'block';
            html2pdf().set({
                margin: 10, filename: 'FarmReport.pdf', image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 }, jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
            }).from(element).save().then(() => { element.style.display = 'none'; });
        }
    </script>
</body>
</html>