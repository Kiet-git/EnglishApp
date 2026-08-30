<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'config/db.php';
include 'includes/header.php';

// Lấy danh sách chủ đề
$stmt = $conn->query("SELECT * FROM topics ORDER BY topic_id DESC LIMIT 8");
$topics = $stmt->fetchAll();
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

        /* 4. HIỆU ỨNG FORM INPUT */
        input[type="text"], input[type="password"], textarea, select {
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input:focus, textarea:focus, select:focus {
            border-color: var(--primary-color, #3498db);
            box-shadow: 0 0 8px rgba(52, 152, 219, 0.3); /* Phát sáng nhẹ khi gõ */
            outline: none;
        }

        /* 5. HIỆU ỨNG BẢNG (TABLE) */
        table tbody tr {
            transition: background-color 0.2s ease;
        }
        table tbody tr:hover {
            background-color: #f1f7ff; /* Đổi màu nền nhẹ khi di chuột qua dòng */
            cursor: default;
        }
    /* =========================================
       HERO SECTION (Banner chính)
       ========================================= */
    .hero-section {

        width: 100%;
        min-height: 94vh;

        background-image: 
            linear-gradient(rgba(238,245,255,0.9), rgba(238,247,255,0.9)),
            url('assets/images/bg.png');

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

    /* Thanh tìm kiếm */
    .search-container {
        max-width: 600px;
        margin: 40px auto;
        background: white;
        padding: 10px;
        border-radius: 50px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        display: flex;
        position: relative;
        z-index: 10;
    }

    .search-input {
        flex: 1;
        padding: 10px 20px;
        font-size: 1rem;
        border-radius: 50px;
        outline: none;
    }

    .search-btn {
        background: #3498db;
        color: white;
        border: none;
        padding: 10px 25px;
        border-radius: 50px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.3s;
    }

    .search-btn:hover {
        background: #2980b9;
    }

    /* =========================================
       FEATURES SECTION (Tiện ích của chúng tôi)
       ========================================= */
    .features-section {
        max-width: 1200px;
        margin: 60px auto;
        padding: 0 20px;
    }

    .section-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .section-header h2 {
        color: #2c3e50;
        font-size: 2rem;
        margin-bottom: 10px;
    }

    .section-header p {
        color: #7f8c8d;
        max-width: 800px;
        margin: 0 auto;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
        gap: 20px;
    }

    .feature-card {
        background: #fff;
        border: 1px solid #3498db;
        border-radius: 10px;
        padding: 25px;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(52, 152, 219, 0.15);
    }

    .feature-title {
        color: #2980b9;
        font-size: 1.2rem;
        font-weight: bold;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .feature-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .feature-list li {
        margin-bottom: 10px;
        color: #34495e;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .feature-list li i {
        color: #3498db;
        margin-top: 4px;
    }

    /* =========================================
       TOPICS SECTION (Danh sách chủ đề)
       ========================================= */
    .topics-section {
        max-width: 1200px;
        margin: 0 auto 60px auto;
        padding: 0 20px;
    }
    
    @media (max-width: 768px) {
        .features-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="hero-section">
    <div style="justify-content: center; align-items: center;">
        <h1 class="hero-title" style="margin-top: 0;color:var(--primary-color)">Ôn luyện và Ghi nhớ Từ vựng toàn diện</h1>
        <p class="hero-subtitle">
            Truy cập ngân hàng từ vựng phong phú, luyện tập qua các bài đọc hiểu chuyên sâu và sử dụng công nghệ AI để tạo lộ trình học tập phù hợp nhất với trình độ của bạn – mọi lúc, mọi nơi.
        </p>
        <div class="hero-buttons">
            <a href="modules/topics/topics_list.php" class="btn-orange">
                <i class="fas fa-layer-group"></i> Từ vựng theo Chủ đề
            </a>
            <a href="modules/reading/reading_list.php" class="btn-outline">
                <i class="fas fa-book-open"></i> Từ vựng với Bài đọc
            </a>
        </div>
        <div class="search-container">
            <form action="/EnglishApp/search.php" method="GET" style="display: flex; width: 100%; margin: 0;">
                <input required type="text" name="q" class="search-input" placeholder="Tìm kiếm từ vựng tiếng Anh hoặc tiếng Việt..." style="margin: 0;border: none;border-radius: 20px;margin-right: 20px;">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Tra từ</button>
            </form>
        </div>
    </div>    
</div>

<section style="margin-top: 60px;">
    <div class="topics-section">
        <h2 style="color: var(--primary-color); border-left: 5px solid var(--primary-color); padding-left: 15px; margin-bottom: 25px; text-transform: uppercase;">
            Khám phá các chủ đề bài học 
            <a href="modules/topics/topics_list.php" title="Xem tất cả Chủ đề" style="padding-left: 3px;">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </a>
        </h2>
        <div class="topic-grid">
            <?php foreach($topics as $t): ?>
            <div class="topic-card" style="display: flex; flex-direction: column; height: 100%; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #eee;">
                
                <div style="height: 150px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f9f9f9;">
                    <?php if (!empty($t['image'])): ?>
                        <img src="uploads/<?= $t['image'] ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;">
                    <?php else: ?>
                        <span style="color: #ccc; font-size: 40px;"><i class="fas fa-images"></i></span>
                    <?php endif; ?>
                </div>
                
                <div style="padding: 20px; display: flex; flex-direction: column; flex-grow: 1;">
                    <h3 style="margin-top: 0; color: #2c3e50;"><?= htmlspecialchars($t['topic_name']) ?></h3>
                    <p style="color: #7f8c8d; font-size: 0.95rem; line-height: 1.5; margin-bottom: 20px;">
                        <?= htmlspecialchars($t['description']) ?>
                    </p>
                    
                    <a href="modules/topics/topics_vocab.php?topic_id=<?= $t['topic_id'] ?>" class="btn" style="margin-top: auto; display: block; text-align: center; background: #3498db; color: white; padding: 10px; border-radius: 6px;">
                        <i class="fas fa-play-circle"></i> Học ngay
                    </a>
                </div>

            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section style="border-top:1px solid var(--primary-color); margin-bottom:20px;">
    <div class="features-section">
        <div class="section-header">
            <h2 style="color:var(--primary-color)">Tiện ích học tập của chúng tôi</h2>
            <p>Nền tảng học từ vựng chuyên biệt, hỗ trợ toàn diện từ học theo chủ đề, luyện đọc hiểu, chấm điểm tức thì đến cá nhân hóa nội dung bằng AI tiên tiến.</p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-title">Học từ vựng theo chủ đề</div>
                <ul class="feature-list">
                    <li><i class="fas fa-check-circle"></i> Phân loại rõ ràng, từ vựng sát với giao tiếp thực tế</li>
                    <li><i class="fas fa-check-circle"></i> Tích hợp phát âm chuẩn, phiên âm và ví dụ minh họa</li>
                    <li><i class="fas fa-check-circle"></i> Giao diện thẻ từ (Flashcard) trực quan, dễ thao tác</li>
                </ul>
            </div>

            <div class="feature-card">
                <div class="feature-title">Từ vựng qua bài đọc (Reading)</div>
                <ul class="feature-list">
                    <li><i class="fas fa-check-circle"></i> Cải thiện đọc hiểu và ghi nhớ từ vựng theo đúng ngữ cảnh</li>
                    <li><i class="fas fa-check-circle"></i> Click trực tiếp vào từ trong đoạn văn để xem nghĩa và nghe phát âm</li>
                    <li><i class="fas fa-check-circle"></i> Đa dạng cấp độ bài đọc chuẩn quốc tế từ A1 đến C1 (CEFR)</li>
                </ul>
            </div>

            <div class="feature-card">
                <div class="feature-title">Tích hợp AI cá nhân hóa</div>
                <ul class="feature-list">
                    <li><i class="fas fa-check-circle"></i> Ứng dụng AI để tạo bài đọc theo mọi chủ đề bạn muốn</li>
                    <li><i class="fas fa-check-circle"></i> Tự động bóc tách từ vựng quan trọng từ văn bản được tạo</li>
                    <li><i class="fas fa-check-circle"></i> Miễn phí lượt tạo bài đầu tiên cho mỗi người dùng</li>
                </ul>
            </div>

            <div class="feature-card">
                <div class="feature-title">Kiểm tra & Đánh giá năng lực</div>
                <ul class="feature-list">
                    <li><i class="fas fa-check-circle"></i> Tự động tạo bài tập trắc nghiệm (Quiz) ôn tập từ vựng</li>
                    <li><i class="fas fa-check-circle"></i> Chấm điểm chính xác và trả kết quả ngay lập tức</li>
                    <li><i class="fas fa-check-circle"></i> Ôn tập linh hoạt giúp khắc sâu trí nhớ dài hạn</li>
                </ul>
            </div>
        </div>
    </div>
    <div style="justify-content: center;display: flex;">
        <a href="payments/topup.php" class="btn">Trải nghiệm các gói ưu đãi <i class="fa-solid fa-arrow-right"></i></a>
    </div>
</section>




<?php include 'includes/footer.php'; ?>

<a href="https://zalo.me/0774892895" target="_blank" style="
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    background: #0068ff;
    color: white;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    text-decoration: none;
    font-weight: bold;
    font-size: 12px;
    transition: transform 0.3s;">
    Zalo
</a>