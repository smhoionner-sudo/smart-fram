<?php
session_start();
include "./db.php";

// เช็ค Admin
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

// --- Auto Delete: ลบประกาศที่หมดอายุแล้วอัตโนมัติ ---
$conn->query("DELETE FROM calendar_events WHERE end_date IS NOT NULL AND end_date != '0000-00-00 00:00:00' AND end_date < NOW()");
// --------------------------------------------------

$msg = "";

// --- Backend Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. เพิ่มกิจกรรม
    if (isset($_POST['add_event'])) {
        $title = trim($_POST['title']);
        $start = $_POST['start_date'];
        $end = !empty($_POST['end_date']) ? $_POST['end_date'] : NULL;
        $desc = trim($_POST['description']);
        $color = $_POST['color'];
        $is_global = isset($_POST['is_global']) ? 1 : 0;

        $stmt = $conn->prepare("INSERT INTO calendar_events (user_id, title, description, start_date, end_date, color, is_global) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssi", $_SESSION['userid'], $title, $desc, $start, $end, $color, $is_global);
        if($stmt->execute()) {
            $msg = "<div class='alert alert-success shadow-sm border-0 rounded-3 mb-4 d-flex align-items-center'><i class='fas fa-check-circle me-3 fs-4'></i><div><strong>สำเร็จ!</strong> เพิ่มกิจกรรมเรียบร้อยแล้ว</div><button type='button' class='btn-close ms-auto' data-bs-dismiss='alert'></button></div>";
        }
    }

    // 2. แก้ไขกิจกรรม
    if (isset($_POST['edit_event'])) {
        $id = $_POST['event_id'];
        $title = trim($_POST['edit_title']);
        $desc = trim($_POST['edit_description']);
        $color = $_POST['edit_color'];
        $is_global = isset($_POST['edit_is_global']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE calendar_events SET title=?, description=?, color=?, is_global=? WHERE id=?");
        $stmt->bind_param("sssii", $title, $desc, $color, $is_global, $id);
        if($stmt->execute()) {
            $msg = "<div class='alert alert-primary shadow-sm border-0 rounded-3 mb-4 d-flex align-items-center'><i class='fas fa-info-circle me-3 fs-4'></i><div><strong>อัปเดต!</strong> แก้ไขข้อมูลเรียบร้อยแล้ว</div><button type='button' class='btn-close ms-auto' data-bs-dismiss='alert'></button></div>";
        }
    }

    // 3. ลบข้อมูล
    if (isset($_POST['delete_id'])) {
        $id = $_POST['delete_id'];
        $type = $_POST['delete_type'];

        if ($type === 'event') {
            $stmt = $conn->prepare("DELETE FROM calendar_events WHERE id = ?");
            $stmt->bind_param("i", $id);
        } else {
            $stmt = $conn->prepare("DELETE FROM agricultural_logs WHERE id = ?");
            $stmt->bind_param("i", $id);
        }
        
        if($stmt->execute()) {
            $msg = "<div class='alert alert-dark shadow-sm border-0 rounded-3 mb-4 d-flex align-items-center'><i class='fas fa-trash-alt me-3 fs-4'></i><div><strong>ลบแล้ว!</strong> ข้อมูลถูกนำออกจากระบบ</div><button type='button' class='btn-close ms-auto' data-bs-dismiss='alert'></button></div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ปฏิทินผู้ดูแลระบบ</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    
    <style>
        :root {
            --bg-body: #f3f4f6; --card-bg: #ffffff; --text-primary: #1f2937; --text-secondary: #6b7280;
            --border-color: #e5e7eb; --agri-green: #588157; --agri-orange: #e76f51;
            --sidebar-width: 260px; --sidebar-bg: #2b3035;
        }

        body { font-family: 'Kanit', sans-serif; background-color: var(--bg-body); color: var(--text-primary); padding-bottom: 60px; }

        /* --- Sidebar & Layout Styles (จำเป็นต้องมีเพื่อให้ไฟล์ include แสดงผลถูกต้อง) --- */
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
        .calendar-card {
            background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            padding: 20px; overflow: hidden;
        }

        .btn-add {
            background-color: var(--agri-orange); color: white; border: none; border-radius: 12px;
            padding: 10px 20px; font-weight: 500; transition: all 0.2s; box-shadow: 0 4px 6px rgba(231, 111, 81, 0.2);
        }
        .btn-add:hover { background-color: #d65a3b; color: white; transform: translateY(-2px); }

        .search-input {
            border-radius: 12px; border: 1px solid var(--border-color); padding: 12px 15px; font-size: 0.95rem;
            background-color: white;
        }
        .search-input:focus { border-color: var(--agri-green); box-shadow: 0 0 0 4px rgba(88, 129, 87, 0.1); }

        /* Legend */
        .legend-container { display: flex; flex-wrap: wrap; gap: 8px; }
        .legend-chip {
            display: inline-flex; align-items: center; background: white; padding: 6px 12px;
            border-radius: 8px; font-size: 0.85rem; color: var(--text-secondary);
            border: 1px solid var(--border-color); font-weight: 500;
        }
        .legend-dot { width: 8px; height: 8px; border-radius: 50%; margin-right: 8px; }

        /* FullCalendar Customization */
        .fc-theme-standard td, .fc-theme-standard th { border-color: #f3f4f6; }
        .fc-col-header-cell-cushion { color: var(--text-secondary); font-weight: 500; padding: 12px 0 !important; text-decoration: none; }
        .fc-daygrid-day-number { color: var(--text-secondary); font-weight: 500; text-decoration: none; padding: 8px; }
        .fc .fc-toolbar-title { font-size: 1.5rem; font-weight: 600; color: var(--text-primary); }
        .fc .fc-button {
            border-radius: 8px; text-transform: capitalize; font-weight: 500; padding: 8px 16px;
            background-color: white; border: 1px solid var(--border-color); color: var(--text-primary);
            box-shadow: none; transition: all 0.2s;
        }
        .fc .fc-button:hover, .fc .fc-button-active {
            background-color: #f9fafb !important; color: black !important; border-color: #d1d5db !important;
        }
        .fc-event {
            border-radius: 6px; border: none; padding: 3px 6px; font-size: 0.85rem; margin-bottom: 2px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05); cursor: pointer;
        }
        .fc-day-today { background-color: #f9fafb !important; }

        @media (max-width: 768px) {
            .fc .fc-toolbar { flex-direction: column; gap: 15px; align-items: flex-start; }
            .fc .fc-toolbar-title { font-size: 1.2rem; }
            .calendar-card { padding: 15px; border-radius: 16px; }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <?php include 'mobile_menu.php'; ?>

    <main class="main-content">
        <div class="container-fluid p-0">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center">
                    <div class="d-lg-none me-3"></div>
                    <div>
                        <h3 class="fw-bold text-dark mb-0">ปฏิทิน & ประกาศ</h3>
                        <span class="text-secondary small">จัดการกิจกรรมและติดตามสถานะการเพาะปลูก</span>
                    </div>
                </div>
                
                <button class="btn btn-add d-none d-md-block" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-bullhorn me-2"></i>สร้างประกาศ
                </button>
            </div>
            
            <?= $msg ?>

            <div class="row g-3 mb-4">
                <div class="col-md-6 order-md-2">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 ps-3 border-color-gray"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="searchInput" class="form-control search-input border-start-0 ps-0" placeholder="ค้นหากิจกรรม, ชื่อเกษตรกร...">
                    </div>
                </div>
                <div class="col-md-6 order-md-1 d-flex align-items-center">
                    <div class="legend-container">
                        <div class="legend-chip"><span class="legend-dot" style="background:#E76F51;"></span>ประกาศ</div>
                        <div class="legend-chip"><span class="legend-dot" style="background:#A3B18A;"></span>ข้าว</div>
                        <div class="legend-chip"><span class="legend-dot" style="background:#D4A373;"></span>ลำไย</div>
                        <div class="legend-chip"><span class="legend-dot" style="background:#52796F;"></span>ยาง</div>
                    </div>
                </div>
            </div>

            <div class="calendar-card">
                <div id='calendar'></div>
            </div>
        </div>
    </main>

    <button class="btn btn-add position-fixed d-md-none rounded-circle shadow-lg" 
            style="bottom: 25px; right: 25px; width: 60px; height: 60px; z-index: 1000;" 
            data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="fas fa-plus fs-4"></i>
    </button>

    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark ps-2">สร้างกิจกรรมใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4 pt-3">
                        <div class="alert alert-light border small text-muted">
                            <i class="fas fa-info-circle me-1"></i> ประกาศที่มีเวลาสิ้นสุด จะถูกลบอัตโนมัติเมื่อถึงเวลา
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-secondary fw-bold">หัวข้อกิจกรรม</label>
                            <input type="text" name="title" class="form-control" placeholder="เช่น แจ้งกำหนดการฉีดวัคซีนพืช" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label small text-secondary fw-bold">เริ่ม</label>
                                <input type="datetime-local" name="start_date" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-secondary fw-bold">สิ้นสุด (ถ้ามี)</label>
                                <input type="datetime-local" name="end_date" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-secondary fw-bold">รายละเอียด</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="รายละเอียดเพิ่มเติม..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-secondary fw-bold">สีป้ายกำกับ</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="color" class="form-control form-control-color" value="#E76F51" title="เลือกสี">
                                <span class="text-muted small">เลือกสีที่ต้องการ</span>
                            </div>
                        </div>
                        <div class="form-check form-switch bg-light p-3 rounded-3 border">
                            <input class="form-check-input" type="checkbox" name="is_global" id="globalCheck" checked>
                            <label class="form-check-label fw-bold text-danger" for="globalCheck">
                                <i class="fas fa-globe me-2"></i> ประกาศถึง User ทุกคน
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="add_event" class="btn btn-dark rounded-pill px-4">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header text-white border-0" id="viewHeader" style="min-height: 80px;">
                    <h5 class="modal-title fw-bold position-relative z-1" id="viewTitle"></h5>
                    <button type="button" class="btn-close btn-close-white position-relative z-1" data-bs-dismiss="modal"></button>
                    <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark opacity-25"></div>
                </div>
                
                <div class="modal-body p-4">
                    <div id="viewMode">
                        <div class="d-flex align-items-center text-secondary mb-4">
                            <div class="bg-light p-2 rounded-circle me-3">
                                <i class="far fa-clock fs-5 text-dark"></i>
                            </div>
                            <div>
                                <small class="d-block text-muted">เวลา</small>
                                <span id="viewTime" class="fw-medium text-dark"></span>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <small class="d-block text-muted mb-1">รายละเอียด</small>
                            <p id="viewDesc" class="text-dark bg-light p-3 rounded-3 border mb-0"></p>
                        </div>

                        <div class="d-flex align-items-center mb-4">
                             <div class="bg-light p-2 rounded-circle me-3">
                                <i class="fas fa-user text-dark"></i>
                            </div>
                            <div>
                                <small class="d-block text-muted">เจ้าของรายการ</small>
                                <span id="viewOwner" class="fw-bold text-dark"></span>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button id="btnEdit" class="btn btn-outline-dark rounded-pill py-2" onclick="switchMode('edit')">
                                <i class="fas fa-pen me-2"></i> แก้ไขข้อมูล
                            </button>
                            
                            <form method="POST" onsubmit="return confirm('⚠️ ยืนยันการลบ?\nข้อมูลนี้จะหายไปถาวร!');">
                                <input type="hidden" name="delete_id" id="deleteId">
                                <input type="hidden" name="delete_type" id="deleteType">
                                <button type="submit" class="btn btn-light text-danger w-100 rounded-pill py-2 fw-medium">
                                    <i class="fas fa-trash me-2"></i> ลบรายการนี้
                                </button>
                            </form>
                        </div>
                    </div>

                    <div id="editMode" style="display:none;">
                        <form method="POST">
                            <input type="hidden" name="edit_event" value="1">
                            <input type="hidden" name="event_id" id="editId">
                            
                            <div class="mb-3">
                                <label class="form-label small text-secondary fw-bold">หัวข้อ</label>
                                <input type="text" name="edit_title" id="editTitle" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-secondary fw-bold">รายละเอียด</label>
                                <textarea name="edit_description" id="editDesc" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-secondary fw-bold">สี</label>
                                <input type="color" name="edit_color" id="editColor" class="form-control form-control-color w-100">
                            </div>
                            <div class="form-check form-switch mb-4 bg-light p-3 rounded-3 border">
                                <input class="form-check-input" type="checkbox" name="edit_is_global" id="editGlobal">
                                <label class="form-check-label text-danger fw-bold" for="editGlobal">ประกาศถึงทุกคน</label>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-light w-100 rounded-pill" onclick="switchMode('view')">ยกเลิก</button>
                                <button type="submit" class="btn btn-dark w-100 rounded-pill fw-bold">บันทึกการแก้ไข</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var calendar;

        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var isMobile = window.innerWidth < 768;

            calendar = new FullCalendar.Calendar(calendarEl, {
                height: 'auto',
                contentHeight: 'auto',
                initialView: isMobile ? 'listMonth' : 'dayGridMonth',
                locale: 'th',
                headerToolbar: {
                    left: 'prev,next today', center: 'title', right: 'dayGridMonth,listMonth'
                },
                buttonText: {
                    today: 'วันนี้', month: 'เดือน', list: 'รายการ'
                },
                events: 'fetch_admin_events.php',
                
                dateClick: function(info) {
                    var modal = new bootstrap.Modal(document.getElementById('addModal'));
                    var dateStr = info.dateStr;
                    if(dateStr.indexOf('T') === -1) dateStr += 'T09:00';
                    document.querySelector('input[name="start_date"]').value = dateStr;
                    modal.show();
                },

                eventClick: function(info) {
                    var props = info.event.extendedProps;
                    var modal = new bootstrap.Modal(document.getElementById('viewModal'));
                    
                    switchMode('view');

                    document.getElementById('viewTitle').innerText = info.event.title;
                    document.getElementById('viewHeader').style.backgroundColor = info.event.backgroundColor;
                    document.getElementById('viewDesc').innerText = props.detail || props.description || '-';
                    
                    document.getElementById('viewOwner').innerText = props.owner ? props.owner : 'ผู้ดูแลระบบ (Admin)';
                    
                    var options = { hour: '2-digit', minute:'2-digit' };
                    var startTime = info.event.start.toLocaleTimeString('th-TH', options);
                    var timeStr = startTime;

                    if(info.event.end) {
                        var endTime = info.event.end.toLocaleTimeString('th-TH', options);
                        timeStr += " - " + endTime;
                    }
                    
                    var dateOptions = { day: 'numeric', month: 'short', year: 'numeric' };
                    timeStr = info.event.start.toLocaleDateString('th-TH', dateOptions) + " เวลา " + timeStr;

                    document.querySelector('#viewTime').innerText = timeStr;

                    document.getElementById('deleteId').value = (props.type === 'log') ? props.db_id : info.event.id;
                    document.getElementById('deleteType').value = props.type;

                    if(props.can_edit) {
                        document.getElementById('btnEdit').style.display = 'block';
                        document.getElementById('editId').value = info.event.id;
                        let rawTitle = info.event.title.replace('📢 [ประกาศ] ', '').replace('👤 ', '').replace(/\s\(.*?\)$/, ''); 
                        document.getElementById('editTitle').value = rawTitle;
                        document.getElementById('editDesc').value = props.description;
                        document.getElementById('editColor').value = info.event.backgroundColor;
                        document.getElementById('editGlobal').checked = (props.is_global == 1);
                    } else {
                        document.getElementById('btnEdit').style.display = 'none';
                    }

                    modal.show();
                }
            });
            calendar.render();

            document.getElementById('searchInput').addEventListener('keyup', function() {
                var keyword = this.value.toLowerCase();
                var allEvents = calendar.getEvents();
                allEvents.forEach(function(evt) {
                    var title = evt.title.toLowerCase();
                    var owner = (evt.extendedProps.owner || '').toLowerCase();
                    if (title.includes(keyword) || owner.includes(keyword)) {
                        evt.setProp('display', 'auto');
                    } else {
                        evt.setProp('display', 'none');
                    }
                });
            });
        });

        function switchMode(mode) {
            if(mode === 'edit') {
                document.getElementById('viewMode').style.display = 'none';
                document.getElementById('editMode').style.display = 'block';
            } else {
                document.getElementById('viewMode').style.display = 'block';
                document.getElementById('editMode').style.display = 'none';
            }
        }
    </script>
</body>
</html>