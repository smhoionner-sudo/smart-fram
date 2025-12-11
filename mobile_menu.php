<?php
// ใช้ Logic เดียวกันเพื่อหา Active Menu ในมือถือ
$current_page = basename($_SERVER['PHP_SELF']);

// ตั้งชื่อหัวข้อเมนูตามหน้าไฟล์
$page_titles = [
    'admin_dashboard.php' => 'ภาพรวมระบบ',
    'manage_users.php' => 'จัดการสมาชิก',
    'manage_options.php' => 'จัดการตัวเลือก',
    'view_all_production.php' => 'รายการผลิต',
    'edit-production_admin.php' => 'แก้ไขข้อมูล',
    'admin_calendar.php' => 'ปฏิทิน',
    'admin_slider.php' => 'ป้ายข่าว',
    'profile-admin.php' => 'โปรไฟล์ส่วนตัว'
];
$mobile_title = $page_titles[$current_page] ?? 'Admin Panel';
?>

<nav class="mobile-nav d-lg-none">
    <span class="fw-bold fs-5">
        <i class="fas fa-seedling me-2 text-success"></i><?= $mobile_title ?>
    </span>
    <button class="btn border-0 p-0" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu">
        <i class="fas fa-bars fs-4 text-dark"></i>
    </button>
</nav>
<style>
    /* ปรับแต่ง offcanvas-custom ให้โปร่งใสและมีเอฟเฟกต์เบลอ */
    .offcanvas-custom {
        /* สีพื้นหลังสีดำ แบบโปร่งแสง (ปรับเลข 0.85 น้อยลงถ้าอยากให้ใสขึ้น) */
        background-color: rgba(0, 0, 0, 0.75) !important; 
        
        /* เอฟเฟกต์เบลอฉากหลัง (Glassmorphism) */
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px); /* สำหรับ Safari */
        
        /* เส้นขอบด้านล่างบางๆ ให้ดูมีมิติ */
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        
        /* เงาเล็กน้อยเพื่อให้ตัวเมนูลอยเด่นขึ้น */
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }

    /* ปรับสีตัวหนังสือให้ชัดขึ้นเมื่ออยู่บนพื้นหลังโปร่งใส */
    .offcanvas-custom .nav-link-custom {
        color: rgba(255, 255, 255, 0.8);
        transition: all 0.3s;
    }

    /* เมื่อเอาเมาส์ชี้ หรือเป็นเมนูที่ Active */
    .offcanvas-custom .nav-link-custom:hover,
    .offcanvas-custom .nav-link-custom.active {
        background-color: rgba(255, 255, 255, 0.15); /* พื้นหลังปุ่มตอนกด */
        color: #fff;
        border-radius: 10px;
    }
    
    /* ปรับแต่งปุ่มปิด (X) ให้มองเห็นชัดบนพื้นดำ */
    .btn-close-white {
        filter: invert(1) grayscale(100%) brightness(200%);
    }
</style>
<div class="offcanvas offcanvas-top offcanvas-custom d-lg-none" tabindex="-1" id="mobileMenu">
    <div class="offcanvas-header border-bottom border-white border-opacity-10">
        <h5 class="offcanvas-title text-white">เมนูหลัก</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    
    <div class="offcanvas-body p-4 d-flex flex-column">
        <nav class="d-flex flex-column gap-2">
            <a href="admin_dashboard.php" class="nav-link-custom <?= $current_page == 'admin_dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i> ภาพรวม
            </a>
            <a href="manage_users.php" class="nav-link-custom <?= $current_page == 'manage_users.php' ? 'active' : '' ?>">
                <i class="fas fa-users"></i> จัดการสมาชิก
            </a>
            
            <a href="view_all_production.php" class="nav-link-custom <?= $current_page == 'view_all_production.php' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list"></i> รายการผลิต
            </a>
            <a href="edit-production_admin.php" class="nav-link-custom <?= $current_page == 'edit-production_admin.php' ? 'active' : '' ?>">
                <i class="fas fa-edit"></i> แก้ไขข้อมูล
            </a>
            <a href="admin_calendar.php" class="nav-link-custom <?= $current_page == 'admin_calendar.php' ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i> ปฏิทิน
            </a>
            <a href="admin_slider.php" class="nav-link-custom <?= $current_page == 'admin_slider.php' ? 'active' : '' ?>">
                <i class="fas fa-images"></i> จัดการป้ายข่าว
            </a>
        </nav>

        <div class="mt-auto border-top border-white border-opacity-25 pt-4 mt-4">
            <a href="profile-admin.php" class="d-flex align-items-center text-decoration-none text-white mb-3">
                <div class="bg-white text-dark rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 45px; height: 45px; font-weight:bold; font-size: 1.1rem;">
                    <?= strtoupper(mb_substr($admin_fullname ?? 'A', 0, 1)) ?>
                </div>
                <div class="overflow-hidden">
                    <div class="fw-bold text-truncate"><?= htmlspecialchars($admin_fullname ?? 'Admin') ?></div>
                    <div class="small opacity-75">แตะเพื่อแก้ไขโปรไฟล์</div>
                </div>
                <i class="fas fa-chevron-right ms-auto opacity-50"></i>
            </a>
            
            <a href="logout.php" class="btn btn-danger w-100 rounded-pill" onclick="return confirm('ยืนยันการออกจากระบบ?');">
                <i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ
            </a>
        </div>
    </div>
</div>