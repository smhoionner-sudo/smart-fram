<?php
session_start();
include "./db.php";

// 1. ตรวจสอบสิทธิ์ Admin
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

// 2. ส่วนจัดการ Upload ข่าว
if (isset($_POST['upload'])) {
    $title = $_POST['title'];
    $detail = $_POST['detail'];
    $target_dir = "uploads/";

    // ตรวจสอบโฟลเดอร์
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_name = basename($_FILES["fileToUpload"]["name"]);
    // เปลี่ยนชื่อไฟล์เป็น timestamp เพื่อไม่ให้ซ้ำ
    $target_file = $target_dir . time() . "_" . $file_name;

    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
    if ($check !== false) {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            $stmt = $conn->prepare("INSERT INTO news_sliders (image_path, title, detail) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $target_file, $title, $detail);

            if ($stmt->execute()) {
                $alert_message = '<div class="alert alert-success shadow-sm border-0 rounded-3 mb-4"><i class="fas fa-check-circle me-2"></i>บันทึกข่าวและรูปภาพสำเร็จ</div>';
            } else {
                $alert_message = '<div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4">Database Error: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        } else {
            $alert_message = '<div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4">ขออภัย เกิดข้อผิดพลาดในการอัปโหลดไฟล์</div>';
        }
    } else {
        $alert_message = '<div class="alert alert-warning shadow-sm border-0 rounded-3 mb-4">ไฟล์ที่เลือกไม่ใช่รูปภาพ</div>';
    }
}

// 3. ส่วนจัดการลบรูปภาพ
if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    $sql_get = "SELECT image_path FROM news_sliders WHERE id = ?";
    $stmt = $conn->prepare($sql_get);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (file_exists($row['image_path'])) {
            unlink($row['image_path']);
        }

        $del_stmt = $conn->prepare("DELETE FROM news_sliders WHERE id = ?");
        $del_stmt->bind_param("i", $id);
        if ($del_stmt->execute()) {
            $alert_message = '<div class="alert alert-dark shadow-sm border-0 rounded-3 mb-4"><i class="fas fa-trash-alt me-2"></i>ลบข่าวเรียบร้อยแล้ว</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>จัดการข่าวประชาสัมพันธ์ - Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        /* CSS Theme (จำเป็นสำหรับ Sidebar) */
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
        .btn-add { background-color: var(--agri-dark); color: white; border: none; border-radius: 10px; padding: 10px 20px; font-weight: 500; transition: all 0.2s; }
        .btn-add:hover { background-color: #2f4a33; color: white; transform: translateY(-2px); }

        /* News Card */
        .card-news { background: var(--card-bg); border: none; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); transition: all 0.3s ease; height: 100%; overflow: hidden; position: relative; }
        .card-news:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }

        .card-img-wrapper { height: 180px; overflow: hidden; position: relative; }
        .card-img-top { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
        .card-news:hover .card-img-top { transform: scale(1.05); }

        .card-body { padding: 1.25rem; }
        .news-title { font-weight: 600; font-size: 1.1rem; margin-bottom: 0.5rem; color: var(--agri-dark); }
        .news-desc { font-size: 0.9rem; color: var(--text-secondary); line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

        /* Delete Button on Card */
        .btn-delete-card { position: absolute; top: 10px; right: 10px; background: rgba(255, 255, 255, 0.9); color: #ef4444; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: none; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); z-index: 10; }
        .btn-delete-card:hover { background: #ef4444; color: white; transform: scale(1.1); }

        /* Form Styling */
        .form-control, .form-select { border-radius: 10px; border: 1px solid var(--border-color); padding: 10px 15px; }
        .form-control:focus { border-color: var(--agri-green); box-shadow: 0 0 0 4px rgba(88, 129, 87, 0.1); }

        .upload-preview { width: 100%; height: 200px; border-radius: 12px; border: 2px dashed #cbd5e1; background: #f8fafc; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: 15px; position: relative; }
        .upload-preview img { width: 100%; height: 100%; object-fit: cover; display: none; }
        .upload-placeholder { text-align: center; color: #94a3b8; }
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
                        <h3 class="fw-bold text-dark mb-0">จัดการข่าวประชาสัมพันธ์</h3>
                        <span class="text-secondary small">เพิ่ม Slider และข่าวสารกิจกรรม</span>
                    </div>
                </div>

                <button type="button" class="btn btn-add shadow-sm" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="fas fa-plus me-2"></i><span class="d-none d-sm-inline">เพิ่มข่าวใหม่</span>
                </button>
            </div>

            <?= $alert_message ?>

            <div class="row g-4">
                <?php
                $result = $conn->query("SELECT * FROM news_sliders ORDER BY id DESC");
                if ($result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                        ?>
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="card card-news h-100">
                                <div class="card-img-wrapper">
                                    <img src="<?= $row['image_path'] ?>" class="card-img-top" alt="News Image">

                                    <form method="POST"
                                        onsubmit="return confirm('⚠️ ยืนยันการลบข่าวนี้?\n(ไฟล์รูปภาพจะถูกลบออกจาก Server ด้วย)');">
                                        <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn-delete-card" title="ลบข่าว">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                                <div class="card-body">
                                    <h5 class="news-title text-truncate"><?= htmlspecialchars($row['title']) ?></h5>
                                    <p class="news-desc">
                                        <?= $row['detail'] ? nl2br(htmlspecialchars($row['detail'])) : '<span class="text-muted fst-italic">ไม่มีเนื้อหาข่าว</span>' ?>
                                    </p>
                                    <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center">
                                        <span class="text-muted small"><i class="far fa-clock me-1"></i> ID:
                                            #<?= $row['id'] ?></span>
                                        <span class="badge bg-light text-secondary border">Slider Show</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; else: ?>
                    <div class="col-12">
                        <div class="text-center py-5 text-secondary">
                            <div class="bg-white p-5 rounded-4 shadow-sm border border-light"
                                style="display: inline-block;">
                                <i class="far fa-newspaper fa-4x mb-3 text-muted opacity-25"></i>
                                <h5 class="fw-bold">ยังไม่มีรายการข่าว</h5>
                                <p class="mb-0 small">กดปุ่ม "เพิ่มข่าวใหม่" เพื่อเริ่มสร้างประกาศ</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <button class="btn btn-add position-fixed d-md-none rounded-circle shadow-lg"
        style="bottom: 25px; right: 25px; width: 60px; height: 60px; z-index: 1000;" data-bs-toggle="modal"
        data-bs-target="#uploadModal">
        <i class="fas fa-plus fs-4"></i>
    </button>

    <div class="modal fade" id="uploadModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark ps-2">สร้างข่าวประชาสัมพันธ์</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form action="" method="post" enctype="multipart/form-data">
                    <div class="modal-body p-4 pt-3">

                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">รูปภาพปก (แนวนอน)</label>
                            <label for="fileToUpload" class="upload-preview cursor-pointer" style="cursor: pointer;">
                                <div class="upload-placeholder" id="placeholder">
                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i><br>
                                    <span class="small">คลิกเพื่อเลือกรูปภาพ</span>
                                </div>
                                <img id="imgPreview" src="#" alt="Preview">
                            </label>
                            <input type="file" name="fileToUpload" id="fileToUpload" class="d-none" required
                                accept="image/*" onchange="previewImage(this)">
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">หัวข้อข่าว</label>
                            <input type="text" name="title" class="form-control" placeholder="เช่น กิจกรรมลงพื้นที่..."
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">เนื้อหาข่าว</label>
                            <textarea name="detail" class="form-control" rows="5"
                                placeholder="พิมพ์รายละเอียดข่าว..."></textarea>
                        </div>
                    </div>

                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light rounded-pill px-4"
                            data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="upload" class="btn btn-add rounded-pill px-4">
                            <i class="fas fa-save me-2"></i>บันทึกข่าว
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ฟังก์ชันแสดงรูปตัวอย่างก่อนอัปโหลด
        function previewImage(input) {
            const preview = document.getElementById('imgPreview');
            const placeholder = document.getElementById('placeholder');

            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    placeholder.style.display = 'none';
                }

                reader.readAsDataURL(input.files[0]);
            } else {
                preview.src = '#';
                preview.style.display = 'none';
                placeholder.style.display = 'block';
            }
        }
    </script>
</body>

</html>