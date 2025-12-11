<?php
session_start();
include "./db.php";

if (!isset($_SESSION['userid']) || !isset($_GET['id'])) {
    header("Location: history.php");
    exit;
}

$id = $_GET['id'];
$user_id = $_SESSION['userid'];

// ดึงข้อมูลเดิม
$stmt = $conn->prepare("SELECT * FROM agricultural_logs WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    echo "ไม่พบข้อมูล"; exit;
}

// อัปเดตข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_data'])) {
    // รับค่าเหมือนหน้า savedata.php (ตัด crop_type ออก เพราะไม่ควรเปลี่ยนประเภทพืช)
    $activity_date = $_POST['activity_date'];
    
    // Logic การเลือก Variety ตามประเภทพืช
    $crop_type = $row['crop_type'];
    $crop_variety = "";
    if ($crop_type == 'rice') $crop_variety = $_POST['rice_variety'];
    elseif ($crop_type == 'longan') $crop_variety = $_POST['longan_variety'];
    elseif ($crop_type == 'rubber') $crop_variety = $_POST['rubber_variety'];

    // รับค่าอื่น ๆ
    $crop_season = isset($_POST['crop_season']) ? $_POST['crop_season'] : NULL;
    $area_rai = !empty($_POST['area_rai']) ? intval($_POST['area_rai']) : 0;
    $area_ngan = !empty($_POST['area_ngan']) ? intval($_POST['area_ngan']) : 0;
    $area_wah = !empty($_POST['area_wah']) ? intval($_POST['area_wah']) : 0;
    $planting_method = isset($_POST['planting_method']) ? $_POST['planting_method'] : '';
    $tree_amount = !empty($_POST['tree_amount']) ? intval($_POST['tree_amount']) : 0;
    
    // ต้นทุน
    $cost_fertilizer = floatval($_POST['cost_fertilizer']);
    $cost_chemical = floatval($_POST['cost_chemical']);
    $cost_labor = floatval($_POST['cost_labor']);
    $total_cost = $cost_fertilizer + $cost_chemical + $cost_labor;

    $sql = "UPDATE agricultural_logs SET 
            activity_date=?, crop_variety=?, crop_season=?, area_rai=?, area_ngan=?, area_wah=?, 
            planting_method=?, tree_amount=?, cost_fertilizer=?, cost_chemical=?, cost_labor=?, total_cost=? 
            WHERE id=? AND user_id=?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssiiisiddddis", 
        $activity_date, $crop_variety, $crop_season, $area_rai, $area_ngan, $area_wah, 
        $planting_method, $tree_amount, $cost_fertilizer, $cost_chemical, $cost_labor, $total_cost, 
        $id, $user_id
    );

    if ($stmt->execute()) {
        header("Location: history.php"); // แก้เสร็จกลับไปหน้าประวัติ
        exit;
    } else {
        $msg = "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูล</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style> body { font-family: 'Kanit', sans-serif; background: #f8f9fa; } .form-card { background: white; border-radius: 20px; padding: 30px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.05); } </style>
</head>
<body>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <form method="POST" class="form-card">
                    <h4 class="mb-4 text-warning fw-bold">✏️ แก้ไขข้อมูล: <?= $row['activity_name'] ?></h4>
                    
                    <div class="mb-3">
                        <label>วันที่</label>
                        <input type="date" name="activity_date" class="form-control" value="<?= $row['activity_date'] ?>" required>
                    </div>

                    <?php if($row['crop_type'] == 'rice'): ?>
                        <div class="mb-3">
                            <label>สายพันธุ์ข้าว</label>
                            <select name="rice_variety" class="form-select">
                                <option value="<?= $row['crop_variety'] ?>" selected><?= $row['crop_variety'] ?> (เดิม)</option>
                                <option value="ขาวดอกมะลิ 105">ขาวดอกมะลิ 105</option>
                                <option value="กข6">กข6</option>
                                <option value="กข15">กข15</option>
                                <option value="ปทุมธานี 1">ปทุมธานี 1</option>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-4"><label>ไร่</label><input type="number" name="area_rai" class="form-control" value="<?= $row['area_rai'] ?>"></div>
                            <div class="col-4"><label>งาน</label><input type="number" name="area_ngan" class="form-control" value="<?= $row['area_ngan'] ?>"></div>
                            <div class="col-4"><label>วา</label><input type="number" name="area_wah" class="form-control" value="<?= $row['area_wah'] ?>"></div>
                        </div>
                    <?php elseif($row['crop_type'] == 'longan'): ?>
                         <div class="mb-3">
                            <label>สายพันธุ์ลำไย</label>
                            <select name="longan_variety" class="form-select">
                                <option value="<?= $row['crop_variety'] ?>" selected><?= $row['crop_variety'] ?> (เดิม)</option>
                                <option value="อีดอ">อีดอ</option>
                                <option value="สีชมพู">สีชมพู</option>
                            </select>
                        </div>
                        <div class="mb-3"><label>จำนวนต้น</label><input type="number" name="tree_amount" class="form-control" value="<?= $row['tree_amount'] ?>"></div>
                    <?php elseif($row['crop_type'] == 'rubber'): ?>
                         <div class="mb-3">
                            <label>สายพันธุ์ยาง</label>
                            <select name="rubber_variety" class="form-select">
                                <option value="<?= $row['crop_variety'] ?>" selected><?= $row['crop_variety'] ?> (เดิม)</option>
                                <option value="RRIM 600">RRIM 600</option>
                                <option value="RRIT 251">RRIT 251</option>
                            </select>
                        </div>
                        <div class="mb-3"><label>จำนวนต้น</label><input type="number" name="tree_amount" class="form-control" value="<?= $row['tree_amount'] ?>"></div>
                    <?php endif; ?>

                    <hr>
                    <h6>แก้ไขต้นทุน</h6>
                    <div class="row g-2">
                        <div class="col-4"><label class="small">ค่าปุ๋ย</label><input type="number" step="0.01" name="cost_fertilizer" class="form-control" value="<?= $row['cost_fertilizer'] ?>"></div>
                        <div class="col-4"><label class="small">ค่ายา</label><input type="number" step="0.01" name="cost_chemical" class="form-control" value="<?= $row['cost_chemical'] ?>"></div>
                        <div class="col-4"><label class="small">ค่าแรง</label><input type="number" step="0.01" name="cost_labor" class="form-control" value="<?= $row['cost_labor'] ?>"></div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <a href="history.php" class="btn btn-light w-50 rounded-pill">ยกเลิก</a>
                        <button type="submit" name="update_data" class="btn btn-warning w-50 rounded-pill text-white fw-bold">บันทึกการแก้ไข</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>