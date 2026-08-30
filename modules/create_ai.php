<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../config/db.php';
include '../includes/header.php';

?>

<style>
    .container {
        all: unset;
    }
    /* 1. ĐỊNH NGHĨA HIỆU ỨNG FADE IN + TRƯỢT LÊN */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px); /* Dịch xuống 20px */
            }
            to {
                opacity: 1;
                transform: translateY(0); /* Trở về vị trí cũ */
            }
        }

        /* 2. ÁP DỤNG CHO TOÀN BỘ NỘI DUNG */
        body {
            /* Giúp font chữ mượt hơn trên màn hình hiện đại */
            -webkit-font-smoothing: antialiased; 
        }

        /* Class .container là nơi chứa nội dung chính của bạn */
        .container {
            animation: fadeInUp 0.6s ease-out; /* Chạy hiệu ứng trong 0.6 giây */
        }

        /* 3. HIỆU ỨNG NÚT BẤM (HOVER) */
        .btn, button, a {
            transition: all 0.3s ease; /* Mọi thay đổi màu sắc sẽ diễn ra từ từ trong 0.3s */
        }
        
        .btn:hover {
            transform: translateY(-2px); /* Nhấc nhẹ nút lên khi di chuột vào */
            box-shadow: 0 4px 8px rgba(0,0,0,0.15); /* Đổ bóng nhẹ */
        }

    /* =========================================
       HERO SECTION (Banner chính)
       ========================================= */
    .hero-section {

        width: 100%;
        min-height: 94vh;

        background-image: 
            linear-gradient(rgba(238,245,255,0.9), rgba(238,247,255,0.9)),
            url('../assets/images/bg-ai.png');

        background-position: center;   /* căn giữa ảnh */
        background-repeat: no-repeat;  /* không lặp */
        background-size: cover;        /* tự fill toàn màn hình */

        display: flex;
        justify-content: center;
        align-items: center;

        text-align: center;
    }

    .hero-title {
        font-size: 2.5rem;
        color: #2c3e50;
        margin-bottom: 15px;
        font-weight: 800;
    }

    .hero-subtitle {
        font-size: 1.1rem;
        color: #555;
        max-width: 700px;
        margin: 0 auto 30px auto;
        line-height: 1.6;
    }

    .hero-buttons {
        display: flex;
        justify-content: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .btn-orange {
        background-color: var(--primary-color);
        color: white;
        padding: 12px 25px;
        border-radius: 8px;
        font-weight: bold;
        text-decoration: none;
        transition: 0.3s;
        border: 2px solid var(--primary-color);
    }

    .btn-orange:hover {
        transform: translateY(-2px);
        background-color: #1b89e4;
        color: white;
    }

    .btn-outline {
        background-color: transparent;
        color: var(--primary-color);
        padding: 12px 25px;
        border-radius: 8px;
        font-weight: bold;
        text-decoration: none;
        transition: 0.3s;
        border: 2px solid var(--primary-color);
    }

    .btn-outline:hover {
        transform: translateY(-2px);
        background-color: #7fc8ff91;
    }

</style>

<div class="hero-section">
    <div style="justify-content: center; align-items: center;">
        <h1 class="hero-title" style="margin-top: 0;color:var(--primary-color)">Công Cụ Tạo Nội dung Tự Động với AI</h1>
        <p class="hero-subtitle">
            Trải nghiệm các công cụ AI tiên tiến giúp bạn tạo ra các bài thi, bài đọc chất lượng chỉ trong vài giây. Tận dụng sức mạnh của trí tuệ nhân tạo để tiết kiệm thời gian và nâng cao hiệu quả học tập!
        </p>
        <div class="hero-buttons">
            <a href="quiz/create_quiz_ai.php" class="btn-orange">
                <i class="fa-solid fa-feather"></i> Create Quizzes
            </a>
            <a href="reading/create_reading.php" class="btn-outline">
                <i class="fas fa-book-open"></i> Create Readings
            </a>
        </div>
    </div>    
</div>

<?php include '../includes/footer.php'; ?>