<?php
session_start();
include "./db.php";

// 1. Check Admin
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// --- Fetch Admin Info for Sidebar (จำเป็นสำหรับ sidebar.php) ---
$admin_id = $_SESSION['userid'];
$stmt_ad = $conn->prepare("SELECT name, surname FROM users WHERE id = ?");
$stmt_ad->bind_param("i", $admin_id);
$stmt_ad->execute();
$res_ad = $stmt_ad->get_result()->fetch_assoc();
$admin_fullname = ($res_ad['name'] ?? 'Admin') . ' ' . ($res_ad['surname'] ?? '');

$alert_message = "";

// --- Backend Logic ---

// 2. Handle Add Data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_option'])) {
        $category = $_POST['category'];
        $name = trim($_POST['name']);
        
        if (!empty($name)) {
            // Check duplicate
            $checkStmt = $conn->prepare("SELECT id FROM crop_options WHERE category = ? AND name = ?");
            $checkStmt->bind_param("ss", $category, $name);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                 $alert_message = '<div class="alert alert-warning shadow-sm border-0 rounded-3 mb-4"><i class="fas fa-exclamation-triangle me-2"></i>ข้อมูลนี้มีอยู่ในระบบแล้ว</div>';
            } else {
                $stmt = $conn->prepare("INSERT INTO crop_options (category, name) VALUES (?, ?)");
                $stmt->bind_param("ss", $category, $name);
                if ($stmt->execute()) {
                    $alert_message = '<div class="alert alert-success shadow-sm border-0 rounded-3 mb-4"><i class="fas fa-check-circle me-2"></i>เพิ่มข้อมูลสำเร็จ</div>';
                } else {
                    $alert_message = '<div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4">เกิดข้อผิดพลาด</div>';
                }
            }
        }
    }
    
    // 3. Handle Delete
    if (isset($_POST['delete_id'])) {
        $id = $_POST['delete_id'];
        $stmt = $conn->prepare("DELETE FROM crop_options WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
             $alert_message = '<div class="alert alert-dark shadow-sm border-0 rounded-3 mb-4"><i class="fas fa-trash-alt me-2"></i>ลบข้อมูลเรียบร้อยแล้ว</div>';
        }
    }
}

// 4. Fetch Data Grouped by Category
$options = [];
$sql = "SELECT * FROM crop_options ORDER BY name ASC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $options[$row['category']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>จัดการตัวเลือกการเกษตร - Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        /* CSS Theme */
        :root {
            --bg-body: #f3f4f6; --card-bg: #ffffff; --text-primary: #1f2937; --text-secondary: #6b7280;
            --border-color: #e5e7eb; --agri-green: #588157; --agri-dark: #3a5a40;
            --sidebar-width: 260px; --sidebar-bg: #2b3035;
        }

        body { font-family: 'Kanit', sans-serif; background-color: var(--bg-body); color: var(--text-primary); padding-bottom: 50px; }

        /* Sidebar & Layout Styles */
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
        .card-modern {
            background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02); transition: all 0.2s ease; overflow: hidden;
        }
        
        /* Custom Tabs */
        .nav-pills .nav-link {
            color: var(--text-secondary); font-weight: 500; border-radius: 10px;
            padding: 10px 20px; margin-right: 5px; transition: all 0.2s;
        }
        .nav-pills .nav-link:hover { background-color: #e5e7eb; color: var(--agri-dark); }
        .nav-pills .nav-link.active {
            background-color: var(--agri-dark); color: white;
            box-shadow: 0 4px 6px -1px rgba(58, 90, 64, 0.3);
        }
        .badge-count {
            background-color: rgba(255,255,255,0.2); color: white; border-radius: 20px;
            padding: 2px 8px; font-size: 0.75rem; margin-left: 5px;
        }
        .nav-link:not(.active) .badge-count { background-color: #e5e7eb; color: #6b7280; }

        /* List Items */
        .list-group-item {
            border: none; border-bottom: 1px solid var(--border-color); padding: 15px 20px;
            display: flex; align-items: center; justify-content: space-between; transition: background 0.2s;
        }
        .list-group-item:last-child { border-bottom: none; }
        .list-group-item:hover { background-color: #f9fafb; }
        
        .item-icon {
            width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; margin-right: 15px;
        }
        .icon-rice { background-color: #dcfce7; color: #166534; }
        .icon-longan { background-color: #fef3c7; color: #92400e; }
        .icon-rubber { background-color: #e0f2fe; color: #075985; }
        .icon-method { background-color: #f3f4f6; color: #4b5563; }

        /* Buttons & Forms */
        .btn-add {
            background-color: var(--agri-dark); color: white; border: none; border-radius: 10px;
            padding: 10px 20px; font-weight: 500; transition: all 0.2s;
        }
        .btn-add:hover { background-color: #2f4a33; color: white; transform: translateY(-2px); }
        
        .btn-del-mini {
            width: 32px; height: 32px; border-radius: 8px; border: 1px solid #fee2e2;
            background-color: #fff1f2; color: #ef4444; display: flex; align-items: center; justify-content: center;
            transition: all 0.2s;
        }
        .btn-del-mini:hover { background-color: #ef4444; color: white; }

        .form-control, .form-select { border-radius: 10px; border: 1px solid var(--border-color); padding: 10px 15px; }
        .form-control:focus, .form-select:focus { border-color: var(--agri-green); box-shadow: 0 0 0 4px rgba(88, 129, 87, 0.1); }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <?php include 'mobile_menu.php'; ?>

    <main class="main-content">
        <div class="container-fluid p-0">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center">
                    <div class="d-lg-none me-3"></div> <div>
                        <h3 class="fw-bold text-dark mb-0">จัดการข้อมูลตัวเลือก</h3>
                        <span class="text-secondary small">เพิ่ม/ลบ สายพันธุ์พืชและวิธีการปลูก</span>
                    </div>
                </div>
                
                <button type="button" class="btn btn-add shadow-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus me-2"></i><span class="d-none d-sm-inline">เพิ่มข้อมูล</span>
                </button>
            </div>

            <?= $alert_message ?>

            <div class="card-modern p-3 mb-4">
                <div class="row g-3 align-items-center">
                    <div class="col-lg-8">
                         <ul class="nav nav-pills" id="pills-tab" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" id="pills-rice-tab" data-bs-toggle="pill" data-bs-target="#pills-rice" type="button">
                                    <i class="fas fa-seedling me-1"></i> ข้าว <span class="badge-count"><?= count($options['rice_variety'] ?? []) ?></span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="pills-longan-tab" data-bs-toggle="pill" data-bs-target="#pills-longan" type="button">
                                    <i class="fas fa-lemon me-1"></i> ลำไย <span class="badge-count"><?= count($options['longan_variety'] ?? []) ?></span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="pills-rubber-tab" data-bs-toggle="pill" data-bs-target="#pills-rubber" type="button">
                                    <i class="fas fa-tree me-1"></i> ยางพารา <span class="badge-count"><?= count($options['rubber_variety'] ?? []) ?></span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="pills-method-tab" data-bs-toggle="pill" data-bs-target="#pills-method" type="button">
                                    <i class="fas fa-tools me-1"></i> วิธีปลูก <span class="badge-count"><?= count($options['planting_method'] ?? []) ?></span>
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="col-lg-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted ps-3"><i class="fas fa-search"></i></span>
                            <input type="text" id="searchInput" class="form-control border-start-0 ps-0 shadow-none py-2" placeholder="ค้นหารายการ..." onkeyup="filterList()">
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-content" id="pills-tabContent">
                
                <div class="tab-pane fade show active" id="pills-rice">
                    <?php renderOptionList($options['rice_variety'] ?? [], 'icon-rice', 'fa-seedling', 'รายชื่อสายพันธุ์ข้าว'); ?>
                </div>

                <div class="tab-pane fade" id="pills-longan">
                    <?php renderOptionList($options['longan_variety'] ?? [], 'icon-longan', 'fa-lemon', 'รายชื่อสายพันธุ์ลำไย'); ?>
                </div>

                <div class="tab-pane fade" id="pills-rubber">
                    <?php renderOptionList($options['rubber_variety'] ?? [], 'icon-rubber', 'fa-tree', 'รายชื่อสายพันธุ์ยางพารา'); ?>
                </div>

                 <div class="tab-pane fade" id="pills-method">
                    <?php renderOptionList($options['planting_method'] ?? [], 'icon-method', 'fa-tools', 'วิธีการปลูกข้าว'); ?>
                </div>

            </div>
        </div>
    </main>

    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark ps-2">เพิ่มตัวเลือกใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4 pt-3">
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">หมวดหมู่</label>
                            <select name="category" class="form-select" required>
                                <option value="rice_variety">🌾 สายพันธุ์ข้าว</option>
                                <option value="longan_variety">🍋 สายพันธุ์ลำไย</option>
                                <option value="rubber_variety">🌳 สายพันธุ์ยางพารา</option>
                                <option value="planting_method">🚜 วิธีการปลูก (ข้าว)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">ชื่อตัวเลือก</label>
                            <input type="text" name="name" class="form-control" placeholder="เช่น ข้าวหอมมะลิ, หว่านน้ำตม" required>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="save_option" class="btn btn-add rounded-pill px-4">บันทึกข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php
    // Helper Function to Render List
    function renderOptionList($data, $iconClass, $faIcon, $title) {
        echo '<div class="card-modern">';
        echo '<div class="p-3 border-bottom bg-light bg-opacity-50"><h6 class="mb-0 fw-bold text-secondary">'.$title.'</h6></div>';
        echo '<div class="list-group list-group-flush">';
        
        if (empty($data)) {
            echo '<div class="text-center py-5 text-secondary opactiy-50">';
            echo '<i class="fas fa-folder-open fa-2x mb-3 text-muted opacity-25"></i><br>ไม่มีข้อมูลในหมวดหมู่นี้';
            echo '</div>';
        } else {
            foreach ($data as $item) {
                echo '<div class="list-group-item">';
                echo '<div class="d-flex align-items-center">';
                echo '<div class="item-icon '.$iconClass.'"><i class="fas '.$faIcon.'"></i></div>';
                echo '<span class="fw-medium text-dark search-name">'.htmlspecialchars($item['name']).'</span>';
                echo '</div>';
                
                echo '<form method="POST" onsubmit="return confirm(\'ยืนยันการลบข้อมูลนี้?\');" class="m-0">';
                echo '<input type="hidden" name="delete_id" value="'.$item['id'].'">';
                echo '<button type="submit" class="btn-del-mini" title="ลบข้อมูล"><i class="fas fa-trash-alt"></i></button>';
                echo '</form>';
                echo '</div>';
            }
        }
        
        echo '</div></div>';
    }
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple Filter Function
        function filterList() {
            var input = document.getElementById("searchInput");
            var filter = input.value.toUpperCase();
            var activePane = document.querySelector('.tab-pane.active');
            var items = activePane.getElementsByClassName('list-group-item');

            for (var i = 0; i < items.length; i++) {
                var nameSpan = items[i].querySelector(".search-name");
                if (nameSpan) {
                    var txtValue = nameSpan.textContent || nameSpan.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        items[i].style.display = "";
                    } else {
                        items[i].style.display = "none";
                    }
                }
            }
        }

        // Reset filter when changing tabs
        var tabEls = document.querySelectorAll('button[data-bs-toggle="pill"]')
        tabEls.forEach(function(tab) {
            tab.addEventListener('shown.bs.tab', function (event) {
                document.getElementById('searchInput').value = '';
                var allItems = document.getElementsByClassName('list-group-item');
                for (var i = 0; i < allItems.length; i++) {
                    allItems[i].style.display = '';
                }
            })
        })
    </script>
</body>
</html>