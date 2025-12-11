<?php
// หาชื่อไฟล์ปัจจุบัน เช่น admin_dashboard.php เพื่อนำไปเทียบ active menu
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar d-none d-lg-flex">
    <a href="admin_dashboard.php" class="sidebar-logo text-decoration-none text-white fs-4 fw-bold ps-2 mb-4">
        <i class="fas fa-seedling me-2 text-warning"></i> Admin
    </a>
    
    <nav class="d-flex flex-column gap-1">
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

    <div class="user-profile-sidebar">
        <a href="profile-admin.php" class="d-flex align-items-center text-decoration-none text-white overflow-hidden" style="flex-grow: 1;" title="แก้ไขโปรไฟล์">
            <div class="user-avatar flex-shrink-0">
                <?= strtoupper(mb_substr($admin_fullname ?? 'A', 0, 1)) ?>
            </div>
            <div class="overflow-hidden ms-2">
                <div class="text-truncate fw-bold small"><?= htmlspecialchars($admin_fullname ?? 'Admin') ?></div>
                <div style="font-size: 0.7rem; opacity: 0.8;">Administrator</div>
            </div>
        </a>

        <a href="logout.php" class="text-danger ms-2" onclick="return confirm('ยืนยันการออกจากระบบ?');" title="ออกจากระบบ">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</aside>