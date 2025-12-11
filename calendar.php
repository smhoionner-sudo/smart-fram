<?php
session_start();
include "./db.php";

// จำลอง Session ถ้าไม่มี
if (!isset($_SESSION['userid'])) {
    $_SESSION['userid'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['fullname'] = 'Admin Test';
}

// --- Backend Logic (เหมือนเดิม) ---
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... (ใส่ Logic PHP เดิมของคุณตรงนี้) ...
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ปฏิทินงานเกษตร</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    
    <style>
        :root { --primary: #3A5A40; --primary-dark: #344E41; --secondary: #588157; --accent: #A3B18A; --bg: #F3F6F4; --sidebar-width: 250px; }
        body { font-family: 'Sarabun', sans-serif; background-color: var(--bg); padding-bottom: 80px; overscroll-behavior-y: none; }
        h1, h2, h3, h4, h5, .font-head { font-family: 'Kanit', sans-serif; }

        /* --- Sidebar Style (ยกมาจากหน้า Index) --- */
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
            .container { max-width: 1200px; padding-top: 20px; }
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

        /* --- Calendar Card --- */
        .calendar-card {
            background: white; border-radius: 20px; border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); padding: 15px;
            overflow: hidden; min-height: 70vh;
        }

        /* --- FullCalendar Tweaks --- */
        .fc { font-family: 'Sarabun', sans-serif; }
        .fc-toolbar-title { font-family: 'Kanit', sans-serif !important; font-size: 1.1rem !important; color: var(--primary-dark); }
        .fc-button { font-size: 0.85rem !important; }
        .fc-button-primary { background-color: var(--primary) !important; border-color: var(--primary) !important; border-radius: 8px !important; }
        .fc-event { border: none !important; border-radius: 4px !important; cursor: pointer; }
        @media (max-width: 576px) {
            .fc-toolbar { flex-direction: column; gap: 10px; }
            .fc-toolbar-chunk { display: flex; justify-content: space-between; width: 100%; }
        }

        /* --- Legend & Mobile Nav --- */
        .legend-wrapper { overflow-x: auto; white-space: nowrap; -webkit-overflow-scrolling: touch; padding-bottom: 5px; }
        .legend-wrapper::-webkit-scrollbar { display: none; }
        .legend-chip { display: inline-flex; align-items: center; background: white; padding: 6px 12px; border-radius: 50px; font-size: 0.8rem; color: #555; box-shadow: 0 2px 4px rgba(0,0,0,0.03); border: 1px solid #eee; margin-right: 5px; }
        .legend-dot { width: 8px; height: 8px; border-radius: 50%; margin-right: 6px; }

        .bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: white; z-index: 1040; display: flex; justify-content: space-around; padding: 12px 0 25px; box-shadow: 0 -5px 20px rgba(0,0,0,0.05); border-radius: 25px 25px 0 0; }
        .nav-item-m { text-align: center; color: #ccc; text-decoration: none; font-size: 0.7rem; width: 60px; transition: 0.3s; }
        .nav-item-m i { font-size: 1.4rem; display: block; margin-bottom: 4px; }
        .nav-item-m.active { color: var(--primary); font-weight: 600; }
        
        /* FAB for Mobile Only */
        .fab-center { position: fixed; bottom: 40px; left: 50%; transform: translateX(-50%); width: 60px; height: 60px; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; box-shadow: 0 4px 15px rgba(58, 90, 64, 0.4); border: 4px solid #F3F6F4; z-index: 1050; text-decoration: none; transition: transform 0.2s; }
        .fab-center:active { transform: translateX(-50%) scale(0.9); }
        @media (min-width: 992px) { .bottom-nav, .fab-center { display: none !important; } }

        /* --- Modal Mobile Optimization --- */
        @media (max-width: 576px) {
            .modal-dialog-centered { align-items: flex-end; min-height: calc(100% - 1rem); margin: 0; }
            .modal-content { border-bottom-left-radius: 0; border-bottom-right-radius: 0; border-top-left-radius: 25px; border-top-right-radius: 25px; }
        }
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
            <a href="calendar.php" class="nav-link-custom active">
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
        <?= $msg ?>

        <header class="app-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-3 d-lg-none">
                    <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-leaf fa-lg"></i>
                    </div>
                    <div>
                        <div class="font-head fw-bold lh-1" style="font-size: 1.2rem;">สมุดบันทึก</div>
                        <div style="font-size: 0.75rem; opacity: 0.9;">หมู่บ้านแม่ต๋ำต้นโพธิ์</div>
                    </div>
                </div>

                <div class="d-none d-lg-block">
                    <h4 class="font-head fw-bold mb-0 text-dark">ปฏิทินกิจกรรม</h4>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-success d-none d-lg-flex align-items-center gap-2 rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addEventModal">
                    <i class="fas fa-calendar-plus"></i> เพิ่มนัดหมาย
                </button>

                <div class="dropdown">
                    <a href="#" data-bs-toggle="dropdown" class="d-flex align-items-center text-decoration-none gap-2">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['fullname']) ?>&background=random&color=<?= isset($_SESSION['userid'])?'fff':'333' ?>" class="user-avatar">
                        <span class="font-head d-none d-xl-block" style="color: inherit;"><?= $_SESSION['fullname'] ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 mt-2">
                        <li><a class="dropdown-item small" href="profile.php"><i class="fas fa-user me-2"></i>โปรไฟล์</a></li>
                        <li><a class="dropdown-item small text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="container pt-3 pt-lg-4">
            
            <div class="row g-3 mb-3 align-items-center">
                <div class="col-12 col-md-6">
                    <h4 class="font-head fw-bold mb-0 text-dark d-lg-none"><i class="fas fa-calendar-check me-2 text-success"></i>ตารางกิจกรรม</h4>
                </div>
                <div class="col-12 col-md-6">
                    <div class="legend-wrapper d-flex justify-content-md-end">
                        <div class="legend-chip"><span class="legend-dot" style="background:#F9A825;"></span>ข้าว</div>
                        <div class="legend-chip"><span class="legend-dot" style="background:#2E7D32;"></span>ลำไย</div>
                        <div class="legend-chip"><span class="legend-dot" style="background:#1565C0;"></span>ยางพารา</div>
                        <div class="legend-chip"><span class="legend-dot" style="background:#3788d8;"></span>ทั่วไป</div>
                        <div class="legend-chip"><span class="legend-dot" style="background:#E76F51;"></span>สำคัญ</div>
                    </div>
                </div>
            </div>

            <div class="calendar-card">
                <div id='calendar'></div>
            </div>
        </div>

        <button class="fab-center d-lg-none border-0" data-bs-toggle="modal" data-bs-target="#addEventModal">
            <i class="fas fa-plus"></i>
        </button>

        <div class="bottom-nav d-lg-none">
            <a href="index.php" class="nav-item-m">
                <i class="fas fa-home"></i>หน้าหลัก
            </a>
            <a href="calendar.php" class="nav-item-m active">
                <i class="fas fa-calendar-alt"></i>ปฏิทิน
            </a>
            <div style="width: 60px;"></div> <a href="history.php" class="nav-item-m">
                <i class="fas fa-history"></i>ประวัติ
            </a>
            <a href="profile.php" class="nav-item-m">
                <i class="fas fa-user"></i>บัญชี
            </a>
        </div>

        <div class="modal fade" id="addEventModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg border-0">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title font-head"><i class="fas fa-plus-circle me-2"></i>เพิ่มกิจกรรม</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body p-4 bg-light">
                            <div class="card border-0 shadow-sm p-3 mb-3">
                                <label class="form-label small text-muted fw-bold">ชื่อกิจกรรม</label>
                                <input type="text" name="title" class="form-control form-control-lg border-0 bg-light" placeholder="เช่น ใส่ปุ๋ย, เก็บเกี่ยว" required>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small text-muted fw-bold">เริ่ม</label>
                                    <input type="datetime-local" name="start_date" id="inputStart" class="form-control" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted fw-bold">สิ้นสุด</label>
                                    <input type="datetime-local" name="end_date" class="form-control">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small text-muted fw-bold">สีป้ายกำกับ</label>
                                <div class="d-flex gap-3 justify-content-center bg-white p-3 rounded-3 shadow-sm">
                                    <input type="radio" class="btn-check" name="color" id="c1" value="#3788d8" checked>
                                    <label class="btn rounded-circle p-3 position-relative" for="c1" style="background:#3788d8;"></label>

                                    <input type="radio" class="btn-check" name="color" id="c2" value="#E76F51">
                                    <label class="btn rounded-circle p-3" for="c2" style="background:#E76F51;"></label>

                                    <input type="radio" class="btn-check" name="color" id="c3" value="#F9A825">
                                    <label class="btn rounded-circle p-3" for="c3" style="background:#F9A825;"></label>
                                    
                                    <input type="radio" class="btn-check" name="color" id="c4" value="#2E7D32">
                                    <label class="btn rounded-circle p-3" for="c4" style="background:#2E7D32;"></label>
                                    
                                    <input type="radio" class="btn-check" name="color" id="c5" value="#1565C0">
                                    <label class="btn rounded-circle p-3" for="c5" style="background:#1565C0;"></label>
                                </div>
                            </div>

                            <div class="mb-2">
                                 <textarea name="description" class="form-control" rows="2" placeholder="รายละเอียดเพิ่มเติม..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer border-0 bg-white">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" name="add_event" class="btn btn-success rounded-pill px-5 shadow fw-bold">บันทึก</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="viewEventModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg border-0">
                    <div class="modal-header text-white" id="viewHeader" style="background-color: var(--primary);">
                        <h5 class="modal-title font-head fw-bold" id="viewTitle"></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="bg-light rounded-circle p-3 me-3 text-success">
                                <i class="far fa-clock fa-lg"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">เวลากิจกรรม</small>
                                <span id="viewTime" class="fw-bold font-head text-dark" style="font-size:1.1rem"></span>
                            </div>
                        </div>

                        <div class="bg-light p-3 rounded-4 mb-4">
                            <small class="text-muted d-block mb-1">รายละเอียด</small>
                            <p id="viewDesc" class="mb-0 text-secondary"></p>
                        </div>
                        
                        <form method="POST" id="deleteForm">
                            <input type="hidden" name="delete_event_id" id="deleteId">
                            <button type="submit" class="btn btn-danger bg-opacity-10 text-danger border-0 w-100 rounded-pill py-2 fw-bold" onclick="return confirm('ยืนยันลบ?')">
                                <i class="fas fa-trash-alt me-2"></i> ลบกิจกรรมนี้
                            </button>
                        </form>
                        
                        <div id="logInfo" style="display:none;">
                             <a href="history.php" class="btn btn-primary w-100 rounded-pill"><i class="fas fa-file-alt me-2"></i> ดูบันทึกฉบับเต็ม</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            
            function getInitialView() {
                return window.innerWidth < 768 ? 'listMonth' : 'dayGridMonth';
            }

            var calendar = new FullCalendar.Calendar(calendarEl, {
                height: 'auto',
                locale: 'th',
                initialView: getInitialView(),
                themeSystem: 'bootstrap5',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listMonth'
                },
                views: {
                    dayGridMonth: { buttonText: 'เดือน' },
                    timeGridWeek: { buttonText: 'สัปดาห์' },
                    listMonth: { buttonText: 'รายการ' }
                },
                events: 'fetch_events.php', 
                selectable: true,
                
                windowResize: function(view) {
                    if (window.innerWidth < 768) {
                        calendar.changeView('listMonth');
                    } else {
                        calendar.changeView('dayGridMonth');
                    }
                },

                dateClick: function(info) {
                    var modal = new bootstrap.Modal(document.getElementById('addEventModal'));
                    var dateStr = info.dateStr;
                    if(dateStr.indexOf('T') === -1) dateStr += 'T09:00';
                    document.getElementById('inputStart').value = dateStr;
                    modal.show();
                },
                
                eventClick: function(info) {
                    var props = info.event.extendedProps;
                    var modal = new bootstrap.Modal(document.getElementById('viewEventModal'));
                    
                    document.getElementById('viewTitle').innerText = info.event.title;
                    document.getElementById('viewHeader').style.backgroundColor = info.event.backgroundColor;
                    
                    var options = { day: 'numeric', month: 'short', year: '2-digit', hour: '2-digit', minute:'2-digit' };
                    var timeText = info.event.start.toLocaleDateString('th-TH', options);
                    if(info.event.end) timeText += ' - ' + info.event.end.toLocaleDateString('th-TH', options);
                    
                    document.getElementById('viewTime').innerText = timeText;
                    document.getElementById('viewDesc').innerText = props.detail || '-';
                    
                    var delForm = document.getElementById('deleteForm');
                    var logInfo = document.getElementById('logInfo');
                    
                    if (props.type === 'log') {
                        delForm.style.display = 'none';
                        logInfo.style.display = 'block';
                    } else {
                        logInfo.style.display = 'none';
                        delForm.style.display = 'block';
                        document.getElementById('deleteId').value = info.event.id;
                    }
                    modal.show();
                }
            });
            calendar.render();
        });
        
        setTimeout(function() {
            let alert = document.querySelector('.alert');
            if(alert) {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 500);
            }
        }, 3000);
    </script>
</body>
</html>