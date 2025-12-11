<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน - สมุดบันทึกการเกษตร</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-image: url('https://images.unsplash.com/photo-1500382017468-9049fed747ef');
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-card {
            max-width: 420px;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 20px;
            border: 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            padding: 2.5rem 2.5rem;
        }

        /* 8. (แก้ไข) ไอคอนหัวข้อ (เปลี่ยนเป็นไอคอนช่วยเหลือ) */
        .login-card .icon-header {
            font-size: 3.5rem;
            color: #588157; /* สีเขียวหลัก */
            margin-bottom: 1rem;
        }

        .login-card .card-title {
            font-weight: 600;
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            color: #3A5A40; /* สีเขียวเข้ม */
        }
        
        .login-card .card-subtitle {
            color: #6c757d;
            margin-bottom: 2rem; /* เพิ่มระยะห่าง */
            font-weight: 300;
        }
        
        /* 9. (เพิ่ม) สไตล์สำหรับข้อมูลติดต่อ */
        .contact-item {
            font-size: 1.05rem; /* ปรับขนาดเล็กน้อย */
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            color: #333; /* สีข้อความหลัก */
        }
        .contact-item i {
            font-size: 1.4rem;
            width: 30px;
            text-align: center;
            margin-right: 1rem;
            color: #588157; /* สีไอคอนเขียว */
        }
        .contact-item strong {
            color: #3A5A40; /* สีหัวข้อเขียวเข้ม */
            margin-right: 0.5rem;
        }
        
        /* 10. (เพิ่ม) ปรับปุ่มรอง (กลับหน้าหลัก) */
        .btn-secondary {
            border-radius: 10px;
            padding: 0.75rem;
            background-color: #6c757d;
            border-color: #6c757d;
            font-size: 1.1rem;
            font-weight: 500;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #5a6268;
        }
    </style>
</head>
<body>

    <div class="card login-card">
        
        <div class="text-center icon-header">
            <i class="fas fa-hands-helping"></i>
        </div>

        <h3 class="text-center card-title">ลืมรหัสผ่าน</h3>
        <p class="text-center card-subtitle">
            กรุณาติดต่อผู้ดูแลระบบ
        </p>

        <div class="mt-2">
            <div class="contact-item">
                <i class="fas fa-user-shield"></i> 
                <div>
                    <strong>ผู้ดูแล:</strong> [ภูมิพัฒน์ ใจคง]
                </div>
            </div>
            <div class="contact-item">
                <i class="fas fa-phone"></i> 
                <div>
                    <strong>โทร:</strong> [061-668-0610]
                </div>
            </div>
            <div class="contact-item">
                <i class="fab fa-line" style="color: #00B900;"></i> 
                <div>
                    <strong>LINE ID:</strong> [admin_1]
                </div>
            </div>
        </div>
        <hr class="my-4">

        <a href="login.php" class="btn btn-secondary w-100">กลับไปหน้าเข้าสู่ระบบ</a>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
</body>
</html>