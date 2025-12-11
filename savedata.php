<?php
session_start();
include "./db.php";

// 1. ตรวจสอบ Login
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit;
}

// 2. สร้าง CSRF Token ถ้ายังไม่มี (ป้องกันการแอบอ้างส่งฟอร์ม)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- ฟังก์ชันป้องกัน XSS ---
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// --- ฟังก์ชัน Render Options ---
function renderOptions($data_array) {
    if (!empty($data_array)) {
        foreach ($data_array as $val) {
            echo '<option value="' . h($val) . '">' . h($val) . '</option>';
        }
    }
}

// --- 3. ดึงตัวเลือกจาก Admin ---
$options_db = [];
$sql_opt = "SELECT * FROM crop_options ORDER BY name ASC";
$result_opt = $conn->query($sql_opt);
while ($row = $result_opt->fetch_assoc()) {
    $options_db[$row['category']][] = $row['name'];
}

$msg = "";

// --- 4. บันทึกข้อมูล ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_data'])) {
    
    // 4.1 ตรวจสอบ CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security Warning: CSRF Token Mismatch");
    }

    $user_id = $_SESSION['userid'];
    $crop_type = $_POST['crop_type']; // รับค่ามาก่อน
    
    // 4.2 Validate: ตรวจสอบว่า crop_type ถูกต้องตามที่กำหนดเท่านั้น
    $allowed_crops = ['rice', 'longan', 'rubber'];
    if (!in_array($crop_type, $allowed_crops)) {
        $msg = "<div class='alert-float alert alert-danger shadow-sm'>รูปแบบข้อมูลพืชไม่ถูกต้อง</div>";
    } else {
        // ถ้าถูกต้อง ให้ทำงานต่อ
        $activity_date = $_POST['activity_date'];
        
        $crop_variety = "";
        if ($crop_type == 'rice') $crop_variety = $_POST['rice_variety'] ?? '';
        elseif ($crop_type == 'longan') $crop_variety = $_POST['longan_variety'] ?? '';
        elseif ($crop_type == 'rubber') $crop_variety = $_POST['rubber_variety'] ?? '';

        $crop_season = $_POST['crop_season'] ?? NULL;
        $area_rai = !empty($_POST['area_rai']) ? intval($_POST['area_rai']) : 0;
        $area_ngan = !empty($_POST['area_ngan']) ? intval($_POST['area_ngan']) : 0;
        $area_wah = !empty($_POST['area_wah']) ? intval($_POST['area_wah']) : 0;
        $planting_method = $_POST['planting_method'] ?? '';
        
        $tree_amount = !empty($_POST['tree_amount']) ? intval($_POST['tree_amount']) : 0;
        $cost_fertilizer = !empty($_POST['cost_fertilizer']) ? floatval($_POST['cost_fertilizer']) : 0.00;
        $cost_chemical = !empty($_POST['cost_chemical']) ? floatval($_POST['cost_chemical']) : 0.00;
        $cost_labor = !empty($_POST['cost_labor']) ? floatval($_POST['cost_labor']) : 0.00;
        
        $total_cost = $cost_fertilizer + $cost_chemical + $cost_labor;

        // กำหนดชื่อกิจกรรมให้แม่นยำ
        $crop_names = ['rice' => 'ข้าว', 'longan' => 'ลำไย', 'rubber' => 'ยางพารา'];
        $activity_name = "บันทึกข้อมูล: " . $crop_names[$crop_type];

        $sql = "INSERT INTO agricultural_logs 
                (user_id, crop_type, activity_date, crop_variety, 
                crop_season, area_rai, area_ngan, area_wah, planting_method,
                tree_amount, cost_fertilizer, cost_chemical, cost_labor, total_cost, activity_name) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("issssiiissdddds", 
                $user_id, $crop_type, $activity_date, $crop_variety, 
                $crop_season, $area_rai, $area_ngan, $area_wah, $planting_method,
                $tree_amount, $cost_fertilizer, $cost_chemical, $cost_labor, $total_cost, $activity_name
            );

            if ($stmt->execute()) {
                $msg = "<div class='alert-float alert alert-success shadow-sm'><i class='fas fa-check-circle me-2'></i>บันทึกข้อมูลสำเร็จ!</div>";
                // รีเฟรชหน้าเพื่อเคลียร์ฟอร์ม (ป้องกันกด F5 แล้วส่งซ้ำ)
                echo "<script>setTimeout(() => { window.location.href='index.php'; }, 1500);</script>";
            } else {
                // 4.3 Hide Error: ไม่แสดง Error DB ตรงๆ ให้ User เห็น
                error_log("DB Error: " . $stmt->error); // บันทึกลง Log Server แทน
                $msg = "<div class='alert-float alert alert-danger shadow-sm'>เกิดข้อผิดพลาดในการบันทึกข้อมูล</div>";
            }
            $stmt->close();
        } else {
             $msg = "<div class='alert-float alert alert-danger shadow-sm'>ไม่สามารถเชื่อมต่อฐานข้อมูลได้</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>บันทึกการเพาะปลูก</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* คง Style เดิมไว้ทั้งหมดครับ */
        :root { --primary: #3A5A40; --primary-dark: #344E41; --bg: #F3F6F4; --accent: #A3B18A; --text-dark: #333; }
        body { font-family: 'Sarabun', sans-serif; background-color: var(--bg); padding-bottom: 90px; color: var(--text-dark); }
        h1, h2, h3, h4, h5, .font-head { font-family: 'Kanit', sans-serif; }
        .app-header { background-color: var(--primary); color: white; padding: 15px 20px; position: sticky; top: 0; z-index: 1020; box-shadow: 0 4px 15px rgba(58, 90, 64, 0.2); border-bottom-left-radius: 20px; border-bottom-right-radius: 20px; }
        .custom-card { background: white; border: none; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); padding: 20px; margin-bottom: 20px; }
        .crop-selector-container { display: flex; gap: 10px; justify-content: space-between; }
        .crop-radio { display: none; }
        .crop-card { flex: 1; background: #fff; border: 2px solid #eee; border-radius: 15px; padding: 15px 5px; text-align: center; cursor: pointer; transition: 0.3s; position: relative; overflow: hidden; }
        .crop-card i { font-size: 2rem; display: block; margin-bottom: 5px; color: #ccc; transition: 0.3s; }
        .crop-card span { font-family: 'Kanit'; font-size: 0.9rem; color: #888; font-weight: 500; }
        .crop-radio:checked + .crop-card { border-color: var(--primary); background-color: #eaf2eb; box-shadow: 0 4px 10px rgba(58, 90, 64, 0.15); }
        .crop-radio:checked + .crop-card i { color: var(--primary); transform: scale(1.1); }
        .crop-radio:checked + .crop-card span { color: var(--primary-dark); font-weight: 600; }
        .form-label { font-family: 'Kanit'; font-weight: 500; font-size: 0.9rem; color: #555; margin-bottom: 5px; }
        .form-control, .form-select { border-radius: 12px; padding: 12px 15px; border: 1px solid #eee; background: #fcfcfc; font-size: 0.95rem; transition: 0.2s; }
        .form-control:focus, .form-select:focus { background: white; border-color: var(--accent); box-shadow: 0 0 0 4px rgba(163, 177, 138, 0.2); }
        .input-group-text { background: #f8f9fa; border: 1px solid #eee; border-radius: 12px 0 0 12px; border-right: none; color: #888; }
        .input-group .form-control { border-left: none; }
        .input-group .form-control:not(:first-child) { border-left: 1px solid #eee; border-radius: 0 12px 12px 0; }
        .cost-summary-box { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; border-radius: 15px; padding: 20px; text-align: center; margin-top: 20px; position: relative; overflow: hidden; }
        .cost-summary-box::after { content: ""; position: absolute; top: -20px; right: -20px; width: 80px; height: 80px; background: rgba(255,255,255,0.1); border-radius: 50%; }
        .btn-save-main { background: var(--primary); color: white; border: none; border-radius: 50px; padding: 15px; font-family: 'Kanit'; font-weight: 600; font-size: 1.1rem; width: 100%; transition: 0.2s; box-shadow: 0 5px 15px rgba(58, 90, 64, 0.3); }
        .btn-save-main:hover { background: var(--primary-dark); transform: translateY(-2px); }
        .alert-float { position: fixed; top: 80px; left: 50%; transform: translateX(-50%); z-index: 2000; border-radius: 50px; padding: 10px 25px; animation: slideDown 0.5s ease; font-family: 'Kanit'; width: 90%; max-width: 400px; text-align: center; }
        @keyframes slideDown { from { top: -100px; } to { top: 80px; } }
        .fade-in { animation: fadeIn 0.5s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .bottom-nav { position: fixed; bottom: 0; width: 100%; background: white; z-index: 1000; display: flex; justify-content: space-around; padding: 10px 0 25px; box-shadow: 0 -5px 20px rgba(0,0,0,0.05); border-radius: 25px 25px 0 0; }
        .nav-item-m { text-align: center; color: #bbb; text-decoration: none; font-size: 0.7rem; transition: 0.3s; }
        .nav-item-m i { font-size: 1.4rem; display: block; margin-bottom: 4px; }
        .nav-item-m.active { color: var(--primary); font-weight: 600; }
        .fab-spacer { width: 50px; } 
    </style>
</head>
<body>

    <?= $msg ?>

    <header class="app-header d-flex align-items-center gap-3">
        <a href="index.php" class="text-white"><i class="fas fa-chevron-left fa-lg"></i></a>
        <div>
            <div class="font-head fw-bold lh-1" style="font-size: 1.2rem;">บันทึกข้อมูล</div>
            <div style="font-size: 0.75rem; opacity: 0.8;">เพิ่มรายการเพาะปลูกใหม่</div>
        </div>
    </header>

    <div class="container pt-4">
        <form method="POST" id="logForm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="mb-4">
                <label class="form-label px-2">เลือกชนิดพืช</label>
                <div class="crop-selector-container">
                    <label class="flex-fill">
                        <input type="radio" name="crop_type" value="rice" class="crop-radio" checked onchange="toggleFields()">
                        <div class="crop-card">
                            <i class="fas fa-seedling"></i>
                            <span>นาข้าว</span>
                        </div>
                    </label>
                    <label class="flex-fill">
                        <input type="radio" name="crop_type" value="longan" class="crop-radio" onchange="toggleFields()">
                        <div class="crop-card">
                            <i class="fas fa-lemon"></i> <span>ลำไย</span>
                        </div>
                    </label>
                    <label class="flex-fill">
                        <input type="radio" name="crop_type" value="rubber" class="crop-radio" onchange="toggleFields()">
                        <div class="crop-card">
                            <i class="fas fa-tree"></i>
                            <span>ยางพารา</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="custom-card">
                
                <div class="mb-4">
                    <label class="form-label">วันที่ทำกิจกรรม</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                        <input type="date" name="activity_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div id="rice-fields" class="fade-in">
                    <div class="border-start border-4 border-success ps-3 mb-3">
                        <h6 class="font-head text-success fw-bold">ข้อมูลนาข้าว</h6>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">สายพันธุ์ข้าว</label>
                        <select name="rice_variety" class="form-select">
                            <option value="">-- เลือกสายพันธุ์ --</option>
                            <?php renderOptions($options_db['rice_variety'] ?? []); ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">รอบการผลิต</label>
                            <select name="crop_season" class="form-select">
                                <option value="in_season">นาปี</option>
                                <option value="off_season">นาปรัง</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">วิธีการปลูก</label>
                            <select name="planting_method" class="form-select">
                                <option value="">-- เลือก --</option>
                                <?php renderOptions($options_db['planting_method'] ?? []); ?>
                            </select>
                        </div>
                    </div>
                    <label class="form-label">ขนาดพื้นที่</label>
                    <div class="row g-2">
                        <div class="col-4">
                            <input type="number" name="area_rai" class="form-control text-center" placeholder="0">
                            <small class="text-muted d-block text-center mt-1" style="font-size:0.7rem">ไร่</small>
                        </div>
                        <div class="col-4">
                            <input type="number" name="area_ngan" class="form-control text-center" placeholder="0">
                            <small class="text-muted d-block text-center mt-1" style="font-size:0.7rem">งาน</small>
                        </div>
                        <div class="col-4">
                            <input type="number" name="area_wah" class="form-control text-center" placeholder="0">
                            <small class="text-muted d-block text-center mt-1" style="font-size:0.7rem">ต.ร.ว.</small>
                        </div>
                    </div>
                </div>

                <div id="longan-fields" class="fade-in" style="display:none;">
                    <div class="border-start border-4 border-warning ps-3 mb-3">
                        <h6 class="font-head text-warning fw-bold">ข้อมูลสวนลำไย</h6>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">สายพันธุ์</label>
                        <select name="longan_variety" class="form-select">
                            <option value="">-- เลือกสายพันธุ์ --</option>
                            <?php renderOptions($options_db['longan_variety'] ?? []); ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">จำนวนต้น</label>
                        <div class="input-group">
                            <input type="number" name="tree_amount" class="form-control" placeholder="ระบุจำนวน">
                            <span class="input-group-text bg-white border-start-0">ต้น</span>
                        </div>
                    </div>
                </div>

                <div id="rubber-fields" class="fade-in" style="display:none;">
                     <div class="border-start border-4 border-primary ps-3 mb-3">
                        <h6 class="font-head text-primary fw-bold">ข้อมูลสวนยาง</h6>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">สายพันธุ์</label>
                        <select name="rubber_variety" class="form-select">
                            <option value="">-- เลือกสายพันธุ์ --</option>
                            <?php renderOptions($options_db['rubber_variety'] ?? []); ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">จำนวนต้น</label>
                        <div class="input-group">
                            <input type="number" name="tree_amount" class="form-control" placeholder="ระบุจำนวน">
                            <span class="input-group-text bg-white border-start-0">ต้น</span>
                        </div>
                    </div>
                </div>

            </div>

            <div class="custom-card">
                <h6 class="font-head fw-bold mb-3"><i class="fas fa-coins text-warning me-2"></i>บันทึกค่าใช้จ่าย</h6>
                
                <div class="mb-2">
                    <label class="form-label d-flex justify-content-between">
                        <span>ค่าปุ๋ย</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-leaf text-success"></i></span>
                        <input type="number" step="0.01" name="cost_fertilizer" class="form-control cost-input" placeholder="0.00" oninput="calcTotal()">
                    </div>
                </div>
                
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">ค่ายา/เคมี</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-flask text-danger"></i></span>
                            <input type="number" step="0.01" name="cost_chemical" class="form-control cost-input" placeholder="0" oninput="calcTotal()">
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label">ค่าแรงงาน</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-users text-info"></i></span>
                            <input type="number" step="0.01" name="cost_labor" class="form-control cost-input" placeholder="0" oninput="calcTotal()">
                        </div>
                    </div>
                </div>

                <div class="cost-summary-box">
                    <small class="opacity-75">รวมต้นทุนทั้งหมด</small>
                    <h1 class="mb-0 fw-bold font-head" id="display-total">0.00</h1>
                    <small>บาท</small>
                </div>
            </div>

            <button type="submit" name="save_data" class="btn-save-main mb-5">
                <i class="fas fa-save me-2"></i> บันทึกข้อมูล
            </button>

        </form>
    </div>

    <div class="bottom-nav">
        <a href="index.php" class="nav-item-m"><i class="fas fa-home"></i>หน้าหลัก</a>
        <a href="calendar.php" class="nav-item-m"><i class="fas fa-calendar-alt"></i>ปฏิทิน</a>
        <div class="fab-spacer"></div> <a href="history.php" class="nav-item-m"><i class="fas fa-history"></i>ประวัติ</a>
        <a href="profile.php" class="nav-item-m"><i class="fas fa-user"></i>โปรไฟล์</a>
    </div>

    <div style="position: fixed; bottom: 35px; left: 50%; transform: translateX(-50%); z-index: 1001;">
        <div style="width: 55px; height: 55px; background: var(--primary-dark); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; box-shadow: 0 4px 10px rgba(0,0,0,0.2); border: 4px solid #f3f6f4;">
            <i class="fas fa-plus"></i>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleFields() {
            const cropType = document.querySelector('input[name="crop_type"]:checked').value;
            
            document.getElementById('rice-fields').style.display = 'none';
            document.getElementById('longan-fields').style.display = 'none';
            document.getElementById('rubber-fields').style.display = 'none';

            if (cropType === 'rice') document.getElementById('rice-fields').style.display = 'block';
            else if (cropType === 'longan') document.getElementById('longan-fields').style.display = 'block';
            else if (cropType === 'rubber') document.getElementById('rubber-fields').style.display = 'block';
        }

        function calcTotal() {
            const inputs = document.querySelectorAll('.cost-input');
            let total = 0;
            inputs.forEach(input => {
                let val = parseFloat(input.value);
                if (!isNaN(val)) total += val;
            });
            document.getElementById('display-total').innerText = total.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        setTimeout(function() {
            let alert = document.querySelector('.alert-float');
            if(alert) {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
        }, 3000);

        window.onload = toggleFields;
    </script>
</body>
</html>