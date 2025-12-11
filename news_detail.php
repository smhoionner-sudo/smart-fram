<?php
session_start();
include "./db.php";

// รับค่า ID ข่าว
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$news_id = $_GET['id'];

// ดึงข้อมูลข่าว
$sql = "SELECT * FROM news_sliders WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $news_id);
$stmt->execute();
$result = $stmt->get_result();
$news = $result->fetch_assoc();

if (!$news) {
    echo "ไม่พบข้อมูลข่าวสาร";
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($news['title']) ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        :root { --primary: #3A5A40; --bg: #e0e0e0; }
        
        body { 
            font-family: 'Sarabun', sans-serif; 
            background-color: var(--bg);
            padding-bottom: 50px;
        }

        /* --- A4 Paper Container --- */
        .a4-paper {
            background: white;
            width: 100%;
            max-width: 210mm; /* ขนาดความกว้าง A4 มาตรฐาน */
            min-height: 297mm; /* ขนาดความสูง A4 มาตรฐาน */
            margin: 40px auto; 
            padding: 25mm 20mm; /* ขอบกระดาษ */
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            position: relative;
        }

        /* --- Image Styling for A4 (สำคัญ) --- */
        .news-feature-img {
            width: 100%; /* กว้างเต็มพื้นที่กระดาษ */
            height: auto; /* สูงตามสัดส่วนจริง (ไม่บีบรูป) */
            display: block;
            margin-bottom: 30px;
            border: 1px solid #eee; /* เส้นขอบบางๆ ให้เห็นขอบรูปชัดเจน */
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        h1.news-title {
            font-family: 'Kanit', sans-serif;
            font-weight: 600;
            color: #333;
            font-size: 24pt;
            margin-bottom: 10px;
            line-height: 1.3;
        }

        .news-meta {
            color: #777;
            font-size: 11pt;
            margin-bottom: 25px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        .news-content {
            font-size: 14pt;
            line-height: 1.6;
            color: #222;
            text-align: justify;
            white-space: pre-wrap; /* รักษารูปแบบการเว้นวรรค */
        }

        /* --- Floating Action Buttons --- */
        .action-bar {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            display: flex;
            gap: 10px;
        }
        .btn-circle {
            width: 45px; height: 45px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            transition: 0.2s;
            border: none;
        }
        .btn-circle:hover { transform: scale(1.1); }

        /* --- Responsive Design --- */
        @media (max-width: 992px) { /* มือถือ และ แท็บเล็ต */
            body { background-color: #fff; padding-bottom: 0; }
            .a4-paper {
                width: 100%;
                max-width: none;
                min-height: 100vh;
                margin: 0;
                padding: 20px; /* ลดขอบกระดาษในจอมือถือ */
                box-shadow: none;
            }
            .news-feature-img {
                width: 100%;
                margin-left: 0;
                margin-right: 0;
            }
            h1.news-title { font-size: 20px; }
            .news-content { font-size: 16px; text-align: left; }
            
            /* Sticky Header on Mobile */
            .action-bar {
                position: sticky;
                top: 0;
                left: 0;
                width: 100%;
                background: rgba(255,255,255,0.95);
                backdrop-filter: blur(5px);
                padding: 10px 15px;
                border-bottom: 1px solid #eee;
                margin-bottom: 0;
                box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            }
        }

        /* --- Print Mode --- */
        @media print {
            body { background: white; }
            .a4-paper { margin: 0; box-shadow: none; padding: 0; width: 100%; max-width: 100%; }
            .action-bar { display: none !important; }
            .news-feature-img { max-height: 100vh; page-break-inside: avoid; }
        }
    </style>
</head>
<body>

    <div class="action-bar">
        <a href="index.php" class="btn-circle btn-light text-dark" title="กลับหน้าหลัก">
            <i class="fas fa-arrow-left"></i>
        </a>
        
    </div>

    <div class="a4-paper">
        <img src="<?= htmlspecialchars($news['image_path']) ?>" class="news-feature-img" alt="เอกสารข่าว">
        
        <h1 class="news-title"><?= htmlspecialchars($news['title']) ?></h1>
        
        <div class="news-meta">
            <i class="far fa-calendar-alt me-2"></i> วันที่ประกาศ: <?= date("d/m/", strtotime($news['created_at'] ?? date("Y-m-d"))) . (date("Y", strtotime($news['created_at'] ?? date("Y-m-d")))+543) ?>
            &nbsp;|&nbsp; 
            <i class="fas fa-bullhorn me-2"></i> ฝ่ายประชาสัมพันธ์
        </div>

        <?php if (!empty($news['detail'])): ?>
            <div class="news-content">
                <?= nl2br(htmlspecialchars($news['detail'])) ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>