<?php
require '../config/db.php';

// Kiểm tra session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Chặn nếu chưa đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$active_tab = $_GET['tab'] ?? 'quizzes'; // Mặc định mở tab Quizzes

// ==========================================
// 1. XỬ LÝ XÓA DỮ LIỆU
// ==========================================
// Xóa bài trắc nghiệm
if (isset($_GET['delete_quiz_id'])) {
    $del_id = $_GET['delete_quiz_id'];
    $check = $conn->prepare("SELECT quiz_id FROM quizzes WHERE quiz_id = ? AND created_by = ?");
    $check->execute([$del_id, $user_id]);
    
    if ($check->rowCount() > 0) {
        $conn->prepare("DELETE FROM questions WHERE quiz_id = ?")->execute([$del_id]);
        $conn->prepare("DELETE FROM test_results WHERE quiz_id = ?")->execute([$del_id]);
        $conn->prepare("DELETE FROM quizzes WHERE quiz_id = ?")->execute([$del_id]);
        echo "<script>alert('Đã xóa bài tập thành công!'); window.location.href='../user/my_quizzes.php?tab=quizzes';</script>";
        exit;
    }
}

// Xóa bài đọc
if (isset($_GET['delete_reading_id'])) {
    $del_id = $_GET['delete_reading_id'];
    $stmt = $conn->prepare("DELETE FROM readings WHERE id = ? AND user_id = ?");
    $stmt->execute([$del_id, $user_id]);
    echo "<script>alert('Đã xóa bài đọc thành công!'); window.location='../user/my_quizzes.php?tab=readings';</script>";
    exit;
}

// ==========================================
// 2. LẤY DỮ LIỆU TỪ DATABASE
// ==========================================
// Lấy danh sách Quiz
$stmtQ = $conn->prepare("
    SELECT q.quiz_id, q.title, q.created_at, 
           (SELECT COUNT(*) FROM questions WHERE quiz_id = q.quiz_id) as total_questions 
    FROM quizzes q 
    WHERE q.created_by = ? 
    ORDER BY q.created_at DESC
");
$stmtQ->execute([$user_id]);
$quizzes = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách Reading
$stmtR = $conn->prepare("
    SELECT r.*, t.topic_name 
    FROM readings r 
    LEFT JOIN topics t ON r.topic_id = t.topic_id 
    WHERE r.user_id = ? 
    ORDER BY r.created_at DESC
");
$stmtR->execute([$user_id]);
$readings = $stmtR->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<style>
    .grid-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .item-card {
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: transform 0.3s ease;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .item-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    
    /* Thiết kế Tab */
    .tab-container {
        display: flex;
        border-bottom: 2px solid #ecf0f1;
        margin-bottom: 25px;
        gap: 10px;
    }
    .tab-btn {
        background: transparent;
        border: none;
        padding: 12px 25px;
        font-size: 1.1rem;
        font-weight: bold;
        color: #7f8c8d;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.3s ease;
    }
    .tab-btn:hover {
        color: var(--primary-color);
        background: #f8f9fa;
        border-radius: 8px 8px 0 0;
    }
    .tab-btn.active {
        color: var(--primary-color);
        border-bottom: 3px solid var(--primary-color);
    }
    .tab-content {
        display: none;
        animation: fadeIn 0.4s ease-in-out;
    }
    .tab-content.active {
        display: block;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .level-badge {
        background: #eee; 
        padding: 3px 10px; 
        border-radius: 15px; 
        font-size: 0.8rem;
        font-weight: bold;
        display: inline-block;
        margin-bottom: 10px;
    }
</style>

<div  style="margin-top: 40px;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="color: var(--primary-color); margin: 0;"><i class="fas fa-archive"></i> Kho Lưu Trữ Của Tôi</h2>
        <a href="../user/profile.php" class="btn" style="background: #95a5a6;"><i class="fas fa-arrow-left"></i> Quay lại Profile</a>
    </div>

    <div class="tab-container">
        <button class="tab-btn <?= $active_tab == 'quizzes' ? 'active' : '' ?>" onclick="switchTab('quizzes')">
            <i class="fas fa-tasks"></i> Kho Đề Thi (<?= count($quizzes) ?>)
        </button>
        <button class="tab-btn <?= $active_tab == 'readings' ? 'active' : '' ?>" onclick="switchTab('readings')">
            <i class="fas fa-book-reader"></i> Kho Bài Đọc (<?= count($readings) ?>)
        </button>
    </div>

    <div id="quizzes" class="tab-content <?= $active_tab == 'quizzes' ? 'active' : '' ?>">
        <div style="text-align: right; margin-bottom: 15px;">
            <a href="../modules/quiz/create_quiz_ai.php" class="btn" style="background: var(--primary-color);">
                <i class="fas fa-plus"></i> Tạo Đề Mới (AI)
            </a>
        </div>

        <?php if (empty($quizzes)): ?>
            <div style="text-align: center; padding: 50px; background: #f8f9fa; border-radius: 10px;">
                <i class="fas fa-folder-open" style="font-size: 3rem; color: #bdc3c7; margin-bottom: 15px;"></i>
                <h3 style="color: #7f8c8d;">Bạn chưa tạo đề thi nào</h3>
                <p style="color: #95a5a6;">Hãy sử dụng tính năng tạo đề tự động để kiểm tra kiến thức nhé!</p>
            </div>
        <?php else: ?>
            <div class="grid-container">
                <?php foreach ($quizzes as $q): ?>
                    <div class="item-card" style="border-top: 4px solid var(--primary-color);">
                        <div>
                            <h3 style="margin-top: 0; font-size: 1.1rem; color: var(--primary-color); line-height: 1.4;">
                                <?= htmlspecialchars($q['title']) ?>
                            </h3>
                            <p style="color: #666; font-size: 0.9rem; margin: 10px 0;">
                                <i class="fas fa-question-circle"></i> <?= $q['total_questions'] ?> câu hỏi<br>
                                <i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($q['created_at'])) ?>
                            </p>
                        </div>

                        <div style="margin-top: 15px; display: flex; gap: 10px;">
                            <a href="../modules/quiz/quiz.php?quiz_id=<?= $q['quiz_id'] ?>" class="btn" style="flex: 1; text-align: center;">
                                <i class="fas fa-play"></i> Làm bài
                            </a>
                            <a href="../user/my_quizzes.php?delete_quiz_id=<?= $q['quiz_id'] ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('Bạn chắc chắn muốn xóa bài thi này? Dữ liệu không thể khôi phục!')"
                               style="padding: 10px 15px;">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div id="readings" class="tab-content <?= $active_tab == 'readings' ? 'active' : '' ?>">
        <div style="text-align: right; margin-bottom: 15px;">
            <a href="../modules/reading/create_reading.php" class="btn" style="background: #8e44ad;">
                <i class="fas fa-plus"></i> Tạo Bài Đọc (AI)
            </a>
        </div>

        <?php if (empty($readings)): ?>
            <div style="text-align: center; padding: 50px; background: #f8f9fa; border-radius: 10px;">
                <i class="fas fa-book-open" style="font-size: 3rem; color: #bdc3c7; margin-bottom: 15px;"></i>
                <h3 style="color: #7f8c8d;">Bạn chưa tạo bài đọc nào</h3>
                <p style="color: #95a5a6;">Dùng AI để tạo các bài đọc tiếng Anh cá nhân hóa theo sở thích của bạn!</p>
            </div>
        <?php else: ?>
            <div class="grid-container">
                <?php foreach ($readings as $r): ?>
                    <div class="item-card" style="border-top: 4px solid #8e44ad;">
                        <div>
                            <span class="level-badge"><?= htmlspecialchars($r['level']) ?></span>
                            <h3 style="margin: 0 0 10px 0; font-size: 1.1rem; line-height: 1.4;">
                                <?= htmlspecialchars($r['title']) ?>
                            </h3>
                            <p style="color: #7f8c8d; font-size: 0.85rem; margin: 0;">
                                <i class="fas fa-clock"></i> <?= date('d/m/Y', strtotime($r['created_at'])) ?>
                            </p>
                        </div>

                        <div style="margin-top: 15px; display: flex; gap: 10px;">
                            <a href="../modules/reading/reading.php?id=<?= $r['id'] ?>" class="btn" style="flex: 1; text-align: center; background: #8e44ad;">
                                <i class="fas fa-book-reader"></i> Đọc ngay
                            </a>
                            <a href="../user/my_quizzes.php?delete_reading_id=<?= $r['id'] ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('Bạn chắc chắn muốn xóa bài đọc này?')" 
                               style="padding: 10px 15px;">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
    // Xử lý chuyển đổi qua lại giữa 2 Tabs
    function switchTab(tabId) {
        // Cập nhật giao diện Tabs
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        
        // Kích hoạt tab được click
        document.querySelector(`.tab-btn[onclick="switchTab('${tabId}')"]`).classList.add('active');
        document.getElementById(tabId).classList.add('active');
        
        // Đổi URL mà không load lại trang để lưu lại trạng thái tab hiện tại
        const newUrl = new URL(window.location.href);
        newUrl.searchParams.set('tab', tabId);
        window.history.pushState({}, '', newUrl);
    }
</script>

<?php include '../includes/footer.php'; ?>