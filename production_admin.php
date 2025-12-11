<?php 
session_start();
require_once 'db.php'; 

// สร้าง CSRF Token ถ้ายังไม่มี
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ดึงข้อมูลเกษตรกร (User) เพื่อนำมาใส่ใน Dropdown ตอนเพิ่มข้อมูล
$users_sql = "SELECT id, name, surname, username FROM users WHERE role IN ('user', 'farmer') ORDER BY name ASC";
$users_result = $conn->query($users_sql);

// --- Mock Data สำหรับ Dropdown ต่างๆ (จำลองข้อมูล Master Data) ---
$rice_varieties = [
    ['variety_name' => 'ขาวดอกมะลิ 105'],
    ['variety_name' => 'กข 6 (ข้าวเหนียว)'],
    ['variety_name' => 'กข 15'],
    ['variety_name' => 'ชัยนาท 1'],
    ['variety_name' => 'ปทุมธานี 1'],
    ['variety_name' => 'สันป่าตอง 1']
];
$rice_cycles = [
    ['cycle_name' => 'นาปี'],
    ['cycle_name' => 'นาปรัง']
];
$area_units = [
    ['unit_name' => 'ไร่'],
    ['unit_name' => 'งาน'],
    ['unit_name' => 'ตารางวา']
];
$planting_methods = [
    ['method_name' => 'หว่านน้ำตม'],
    ['method_name' => 'หว่านสำรวย'],
    ['method_name' => 'ปักดำ (เครื่อง)'],
    ['method_name' => 'ปักดำ (มือ)'],
    ['method_name' => 'หยอด']
];
$longan_varieties = [
    ['variety_name' => 'อีดอ'],
    ['variety_name' => 'สีชมพู'],
    ['variety_name' => 'เบี้ยวเขียว'],
    ['variety_name' => 'แห้ว']
];
$rubber_varieties = [
    ['variety_name' => 'RRIM 600'],
    ['variety_name' => 'RRIT 251'],
    ['variety_name' => 'PB 235']
];
?> 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Focus - Production Management</title>
    <link rel="icon" type="image/png" sizes="16x16" href="./images/favicon.png">
    <link href="./css/style.css" rel="stylesheet">
    <!-- เพิ่ม Font Awesome สำหรับไอคอนใน Tabs -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* ปรับแต่ง Tabs ให้สวยงาม */
        .nav-pills-custom .nav-link {
            color: #555;
            background: #f8f9fa;
            border-radius: 0;
            border: 1px solid #ddd;
            margin-right: 5px;
        }
        .nav-pills-custom .nav-link.active {
            background-color: #28a745; /* สีเขียว */
            color: white;
            border-color: #28a745;
        }
        .section-label {
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 10px;
            border-bottom: 2px solid #eee;
            padding-bottom: 5px;
            color: #333;
        }
    </style>
</head>

<body>

    <div id="preloader">
        <div class="sk-three-bounce">
            <div class="sk-child sk-bounce1"></div>
            <div class="sk-child sk-bounce2"></div>
            <div class="sk-child sk-bounce3"></div>
        </div>
    </div>

    <div id="main-wrapper">

        <!-- ******************** Header ******************** -->
        <div class="nav-header">
            <a href="index.html" class="brand-logo">
                <img class="logo-abbr" src="./images/logo.png" alt="">
                <img class="brand-title" src="./images/logo-text.png" alt="">
            </a>
            <div class="nav-control">
                <div class="hamburger"><span class="line"></span><span class="line"></span><span class="line"></span></div>
            </div>
        </div>
        
        <div class="header">
            <div class="header-content">
                <nav class="navbar navbar-expand">
                    <div class="collapse navbar-collapse justify-content-between">
                        <div class="header-left"></div>
                        <ul class="navbar-nav header-right">
                            <li class="nav-item dropdown header-profile">
                                <a class="nav-link" href="#" role="button" data-toggle="dropdown">
                                    <i class="mdi mdi-account"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a href="./page-login.html" class="dropdown-item">
                                        <i class="icon-key"></i> <span class="ml-2">Logout</span>
                                    </a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
        </div>
        <!-- ******************** End Header ******************** -->

        <!-- ******************** Sidebar ******************** -->
        <?php include 'sidebar.html'; ?>
        <!-- ******************** End Sidebar ******************** -->

        <div class="content-body">
            <div class="container-fluid">
                <div class="row page-titles mx-0">
                    <div class="col-sm-6 p-md-0">
                        <div class="welcome-text"><h4>บันทึกการเพาะปลูก (สำหรับผู้ดูแลระบบ)</h4></div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">ข้อมูลการผลิตทั้งหมด (ข้าว, ลำไย, ยางพารา)</h4>
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addProductionModal">
                                    <i class="fa fa-plus"></i> เพิ่มข้อมูลการผลิต
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-responsive-sm">
                                        <thead class="thead-primary">
                                            <tr>
                                                <th>#</th>
                                                <th>ชื่อเกษตรกร</th>
                                                <th>พืช</th>
                                                <th>ปีการผลิต</th>
                                                <th>พื้นที่ (ไร่)</th>
                                                <th>ผลผลิต (กก.)</th>
                                                <th>ราคา/กก.</th>
                                                <th>จัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Join ตาราง production กับ users เพื่อเอาชื่อคนมาแสดง
                                            $sql = "SELECT p.*, u.name, u.surname 
                                                    FROM production p 
                                                    JOIN users u ON p.user_id = u.id 
                                                    ORDER BY p.id DESC";
                                            $result = $conn->query($sql);

                                            if ($result && $result->num_rows > 0) {
                                                while($row = $result->fetch_assoc()) {
                                                    // แปลงชื่อพืชเป็นไทย
                                                    $cropName = '';
                                                    $badgeColor = '';
                                                    switch($row['crop_type']) {
                                                        case 'Rice': case 'ข้าว': $cropName = 'ข้าว'; $badgeColor='badge-success'; break;
                                                        case 'Longan': case 'ลำไย': $cropName = 'ลำไย'; $badgeColor='badge-warning'; break;
                                                        case 'Rubber': case 'ยางพารา': $cropName = 'ยางพารา'; $badgeColor='badge-dark'; break;
                                                        default: $cropName = $row['crop_type']; $badgeColor='badge-secondary';
                                                    }
                                            ?>
                                            <tr>
                                                <th><?php echo $row['id']; ?></th>
                                                <td><?php echo $row['name'] . " " . $row['surname']; ?></td>
                                                <td><span class="badge <?php echo $badgeColor; ?>"><?php echo $cropName; ?></span></td>
                                                <td><?php echo $row['production_year']; ?></td>
                                                <td><?php echo number_format($row['area_size'], 2); ?></td>
                                                <td><?php echo number_format($row['production_yield']); ?></td>
                                                <td><?php echo number_format($row['expected_price'], 2); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-warning edit-btn" 
                                                        data-toggle="modal" 
                                                        data-target="#editProductionModal"
                                                        data-id="<?php echo $row['id']; ?>"
                                                        data-userid="<?php echo $row['user_id']; ?>"
                                                        data-crop="<?php echo $row['crop_type']; ?>"
                                                        data-year="<?php echo $row['production_year']; ?>"
                                                        data-area="<?php echo $row['area_size']; ?>"
                                                        data-yield="<?php echo $row['production_yield']; ?>"
                                                        data-price="<?php echo $row['expected_price']; ?>"
                                                        data-note="<?php echo $row['note']; ?>"
                                                    >
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                    
                                                    <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $row['id']; ?>)" class="btn btn-sm btn-danger">
                                                        <i class="fa fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php 
                                                }
                                            } else {
                                                echo "<tr><td colspan='8' class='text-center'>ไม่มีข้อมูลการผลิต</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <div class="copyright"><p>Copyright © Designed &amp; Developed by Quixkit 2019</p></div>
        </div>
    </div>

    <!-- Modal เพิ่มข้อมูล (แบบใหม่ Tabbed Form) -->
    <div class="modal fade" id="addProductionModal">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">เพิ่มข้อมูลการผลิตใหม่</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    
                    <!-- Tabs Navigation -->
                    <ul class="nav nav-pills nav-pills-custom mb-3" id="cropTab" role="tablist">
                        <li class="nav-item"><a class="nav-link active" id="rice-tab" data-toggle="tab" href="#rice-content"><i class="fas fa-seedling"></i> ข้าว</a></li>
                        <li class="nav-item"><a class="nav-link" id="longan-tab" data-toggle="tab" href="#longan-content"><i class="fas fa-lemon"></i> ลำไย</a></li>
                        <li class="nav-item"><a class="nav-link" id="rubber-tab" data-toggle="tab" href="#rubber-content"><i class="fas fa-tree"></i> ยางพารา</a></li>
                    </ul>

                    <!-- Tabs Content -->
                    <div class="tab-content pt-2">

                        <!-- Tab ข้าว -->
                        <div class="tab-pane fade show active" id="rice-content">
                            <form action="db_production_add.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="crop_type" value="ข้าว">
                                
                                <div class="form-group">
                                    <label class="text-danger font-weight-bold">เลือกเกษตรกร (เจ้าของแปลง) *</label>
                                    <select class="form-control" name="user_id" required>
                                        <option value="">-- เลือกรายชื่อ --</option>
                                        <?php 
                                        if($users_result->num_rows > 0) {
                                            $users_result->data_seek(0);
                                            while($u = $users_result->fetch_assoc()) {
                                                echo "<option value='".$u['id']."'>".$u['name']." ".$u['surname']." (".$u['username'].")</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="section-label mt-0">ข้อมูลทั่วไป</div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>สายพันธุ์ข้าว</label>
                                        <select class="form-control select2" name="variety" required>
                                            <option value="" disabled selected>-- เลือกสายพันธุ์ --</option>
                                            <?php foreach ($rice_varieties as $v): ?>
                                                <option value="<?= htmlspecialchars($v['variety_name']); ?>"><?= htmlspecialchars($v['variety_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>รอบการผลิต</label>
                                        <select class="form-control select2" name="production_year" required> <!-- ใช้ field production_year เพื่อ map กับ DB เดิม -->
                                            <option value="" disabled selected>-- เลือกรอบการผลิต --</option>
                                            <?php foreach ($rice_cycles as $c): ?>
                                                <option value="<?= htmlspecialchars($c['cycle_name']); ?>"><?= htmlspecialchars($c['cycle_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label>วันที่เริ่มปลูก</label>
                                        <input type="date" class="form-control" name="planting_date" required>
                                    </div>
                                    <div class="form-group col-6 col-md-2">
                                        <label>พื้นที่</label>
                                        <input type="number" step="0.01" class="form-control" name="area_size" placeholder="0.00" required>
                                    </div>
                                    <div class="form-group col-6 col-md-2">
                                        <label>หน่วย</label>
                                        <select class="form-control" name="area_unit">
                                             <?php foreach ($area_units as $unit): ?>
                                                <option value="<?php echo $unit['unit_name']; ?>"><?php echo $unit['unit_name']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-6 col-md-4">
                                        <label>วิธีการปลูก</label>
                                        <select class="form-control select2" name="planting_method">
                                            <option value="" disabled selected>-- เลือกวิธีการ --</option>
                                            <?php foreach ($planting_methods as $m): ?>
                                                <option value="<?= htmlspecialchars($m['method_name']); ?>"><?= htmlspecialchars($m['method_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="section-label">ผลผลิตและราคาคาดการณ์</div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>ผลผลิตที่ได้ (กก.)</label>
                                        <input type="number" step="0.01" class="form-control" name="production_yield" value="0">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>ราคาขาย (บาท/กก.)</label>
                                        <input type="number" step="0.01" class="form-control" name="expected_price" value="0">
                                    </div>
                                </div>

                                <div class="section-label">ต้นทุนเบื้องต้น (บาท)</div>
                                <div class="form-row">
                                    <div class="form-group col-4"><label>ค่าปุ๋ย</label><input type="number" class="form-control" name="cost_fertilizer" value="0"></div>
                                    <div class="form-group col-4"><label>ค่ายา</label><input type="number" class="form-control" name="cost_chemicals" value="0"></div>
                                    <div class="form-group col-4"><label>ค่าแรง</label><input type="number" class="form-control" name="cost_labor" value="0"></div>
                                </div>
                                <div class="form-group">
                                    <label>หมายเหตุ</label>
                                    <textarea class="form-control" name="note" rows="2"></textarea>
                                </div>

                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-success btn-block"><i class="fa fa-check-circle mr-2"></i> บันทึกข้อมูลข้าว</button>
                                </div>
                            </form>
                        </div>

                        <!-- Tab ลำไย -->
                        <div class="tab-pane fade" id="longan-content">
                            <form action="db_production_add.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="crop_type" value="ลำไย">
                                
                                <div class="form-group">
                                    <label class="text-danger font-weight-bold">เลือกเกษตรกร (เจ้าของแปลง) *</label>
                                    <select class="form-control" name="user_id" required>
                                        <option value="">-- เลือกรายชื่อ --</option>
                                        <?php 
                                        if($users_result->num_rows > 0) {
                                            $users_result->data_seek(0);
                                            while($u = $users_result->fetch_assoc()) {
                                                echo "<option value='".$u['id']."'>".$u['name']." ".$u['surname']." (".$u['username'].")</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="section-label mt-0">ข้อมูลทั่วไป</div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>สายพันธุ์</label>
                                        <select class="form-control select2" name="variety" required>
                                            <option value="" disabled selected>-- เลือกสายพันธุ์ --</option>
                                            <?php foreach ($longan_varieties as $v): ?>
                                                <option value="<?= htmlspecialchars($v['variety_name']); ?>"><?= htmlspecialchars($v['variety_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>ปีการผลิต</label>
                                        <input type="text" class="form-control" name="production_year" value="<?php echo date('Y')+543; ?>">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label>จำนวนต้น</label>
                                        <input type="number" class="form-control" name="plant_count" placeholder="0">
                                    </div>
                                    <div class="form-group col-6 col-md-2">
                                        <label>พื้นที่</label>
                                        <input type="number" step="0.01" class="form-control" name="area_size" placeholder="0.00" required>
                                    </div>
                                    <div class="form-group col-6 col-md-2">
                                        <label>หน่วย</label>
                                        <select class="form-control" name="area_unit">
                                             <?php foreach ($area_units as $unit): ?>
                                                <option value="<?php echo $unit['unit_name']; ?>"><?php echo $unit['unit_name']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-6 col-md-4">
                                        <label>วันที่เริ่มปลูก/บำรุง</label>
                                        <input type="date" class="form-control" name="planting_date" required>
                                    </div>
                                </div>
                                
                                <div class="section-label">ผลผลิตและราคาคาดการณ์</div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>ผลผลิตที่ได้ (กก.)</label>
                                        <input type="number" step="0.01" class="form-control" name="production_yield" value="0">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>ราคาขาย (บาท/กก.)</label>
                                        <input type="number" step="0.01" class="form-control" name="expected_price" value="0">
                                    </div>
                                </div>

                                <div class="section-label">ต้นทุนเบื้องต้น (บาท)</div>
                                <div class="form-row">
                                    <div class="form-group col-4"><label>ค่าปุ๋ย</label><input type="number" class="form-control" name="cost_fertilizer" value="0"></div>
                                    <div class="form-group col-4"><label>ค่ายา</label><input type="number" class="form-control" name="cost_chemicals" value="0"></div>
                                    <div class="form-group col-4"><label>ค่าแรง</label><input type="number" class="form-control" name="cost_labor" value="0"></div>
                                </div>
                                <div class="form-group">
                                    <label>หมายเหตุ</label>
                                    <textarea class="form-control" name="note" rows="2"></textarea>
                                </div>

                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-success btn-block"><i class="fa fa-check-circle mr-2"></i> บันทึกข้อมูลลำไย</button>
                                </div>
                            </form>
                        </div>

                        <!-- Tab ยางพารา -->
                        <div class="tab-pane fade" id="rubber-content">
                            <form action="db_production_add.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="crop_type" value="ยางพารา">
                                
                                <div class="form-group">
                                    <label class="text-danger font-weight-bold">เลือกเกษตรกร (เจ้าของแปลง) *</label>
                                    <select class="form-control" name="user_id" required>
                                        <option value="">-- เลือกรายชื่อ --</option>
                                        <?php 
                                        if($users_result->num_rows > 0) {
                                            $users_result->data_seek(0);
                                            while($u = $users_result->fetch_assoc()) {
                                                echo "<option value='".$u['id']."'>".$u['name']." ".$u['surname']." (".$u['username'].")</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="section-label mt-0">ข้อมูลทั่วไป</div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>สายพันธุ์</label>
                                        <select class="form-control select2" name="variety" required>
                                            <option value="" disabled selected>-- เลือกสายพันธุ์ --</option>
                                            <?php foreach ($rubber_varieties as $v): ?>
                                                <option value="<?= htmlspecialchars($v['variety_name']); ?>"><?= htmlspecialchars($v['variety_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>ปีการผลิต</label>
                                        <input type="text" class="form-control" name="production_year" value="<?php echo date('Y')+543; ?>">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label>จำนวนต้น</label>
                                        <input type="number" class="form-control" name="plant_count" placeholder="0" required>
                                    </div>
                                    <div class="form-group col-6 col-md-2">
                                        <label>พื้นที่</label>
                                        <input type="number" step="0.01" class="form-control" name="area_size" placeholder="0.00" required>
                                    </div>
                                    <div class="form-group col-6 col-md-2">
                                        <label>หน่วย</label>
                                        <select class="form-control" name="area_unit">
                                             <?php foreach ($area_units as $unit): ?>
                                                <option value="<?php echo $unit['unit_name']; ?>"><?php echo $unit['unit_name']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-6 col-md-4">
                                        <label>วันที่เริ่มกรีด</label>
                                        <input type="date" class="form-control" name="planting_date" required>
                                    </div>
                                </div>

                                <div class="section-label">ผลผลิตและราคาคาดการณ์</div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>ผลผลิตที่ได้ (กก.)</label>
                                        <input type="number" step="0.01" class="form-control" name="production_yield" value="0">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>ราคาขาย (บาท/กก.)</label>
                                        <input type="number" step="0.01" class="form-control" name="expected_price" value="0">
                                    </div>
                                </div>

                                <div class="section-label">ต้นทุนเบื้องต้น (บาท)</div>
                                <div class="form-row">
                                    <div class="form-group col-4"><label>ค่าปุ๋ย</label><input type="number" class="form-control" name="cost_fertilizer" value="0"></div>
                                    <div class="form-group col-4"><label>ค่ายา</label><input type="number" class="form-control" name="cost_chemicals" value="0"></div>
                                    <div class="form-group col-4"><label>ค่าแรง</label><input type="number" class="form-control" name="cost_labor" value="0"></div>
                                </div>
                                <div class="form-group">
                                    <label>หมายเหตุ</label>
                                    <textarea class="form-control" name="note" rows="2"></textarea>
                                </div>

                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-success btn-block"><i class="fa fa-check-circle mr-2"></i> บันทึกข้อมูลยางพารา</button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไขข้อมูล (คงเดิมไว้เนื่องจากไม่ได้ระบุรูปแบบแก้ไข) -->
    <div class="modal fade" id="editProductionModal">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">แก้ไขข้อมูลการผลิต</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <form action="db_production_edit.php" method="POST">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="form-group">
                            <label>เกษตรกร (เจ้าของแปลง)</label>
                            <select class="form-control" name="user_id" id="edit_userid" required>
                                <?php 
                                if($users_result->num_rows > 0) {
                                    $users_result->data_seek(0);
                                    while($u = $users_result->fetch_assoc()) {
                                        echo "<option value='".$u['id']."'>".$u['name']." ".$u['surname']."</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>ประเภทพืช</label>
                                    <select class="form-control" name="crop_type" id="edit_crop" required>
                                        <option value="Rice">ข้าว</option>
                                        <option value="Longan">ลำไย</option>
                                        <option value="Rubber">ยางพารา</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>ปีการผลิต (พ.ศ.)</label>
                                    <input type="text" class="form-control" name="production_year" id="edit_year" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>พื้นที่ปลูก (ไร่)</label>
                                    <input type="number" step="0.01" class="form-control" name="area_size" id="edit_area" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>ผลผลิตที่ได้ (กก.)</label>
                                    <input type="number" step="0.01" class="form-control" name="production_yield" id="edit_yield" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>ราคาขาย (บาท/กก.)</label>
                                    <input type="number" step="0.01" class="form-control" name="expected_price" id="edit_price" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>หมายเหตุ</label>
                            <textarea class="form-control" name="note" id="edit_note" rows="2"></textarea>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-warning">อัปเดตข้อมูล</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="./vendor/global/global.min.js"></script>
    <script src="./js/quixnav-init.js"></script>
    <script src="./js/custom.min.js"></script>

    <script>
        $(document).ready(function(){
            $('.edit-btn').on('click', function(){
                // ดึงค่าจากปุ่มมาใส่ใน Modal แก้ไข
                $('#edit_id').val($(this).data('id'));
                $('#edit_userid').val($(this).data('userid'));
                $('#edit_crop').val($(this).data('crop'));
                $('#edit_year').val($(this).data('year'));
                $('#edit_area').val($(this).data('area'));
                $('#edit_yield').val($(this).data('yield'));
                $('#edit_price').val($(this).data('price'));
                $('#edit_note').val($(this).data('note'));
            });
            
            // ทำให้ Sidebar Active ที่เมนูนี้
            var currentUrl = window.location.pathname.split("/").pop();
            if(currentUrl == "production_admin.php"){
               $('a[href="production_list.php"]').closest('li').addClass('mm-active'); 
            }
        });

        function confirmDelete(id) {
            if(confirm("ยืนยันการลบข้อมูลการผลิตนี้?")) {
                window.location.href = 'db_production_delete.php?id=' + id;
            }
        }
    </script>
</body>
</html>