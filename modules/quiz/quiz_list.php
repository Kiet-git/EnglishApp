<?php
require '../../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// --- [FIX LỖI CREDIT] ---
$stmt_cr = $conn->prepare("SELECT credits FROM users WHERE user_id = ?");
$stmt_cr->execute([$user_id]);
$current_credits = $stmt_cr->fetchColumn(); 
if ($current_credits === false) $current_credits = 0;

// --- XỬ LÝ LỌC & SẮP XẾP ---
$filter = $_GET['filter'] ?? 'newest'; // Mặc định là mới nhất

// Câu SQL cơ bản
$sql_base = "SELECT q.*, u.username, u.role 
             FROM quizzes q
             LEFT JOIN users u ON q.created_by = u.user_id";

// Thay đổi điều kiện WHERE và ORDER BY tùy theo nút được bấm
if ($filter === 'oldest') {
    // Cũ nhất: Hiển thị cả bài hệ thống + bài của tôi, sắp xếp cũ -> mới (ASC)
    $sql = $sql_base . " WHERE (q.created_by = ? OR u.role = 'admin' OR u.role IS NULL) ORDER BY q.quiz_id ASC";
    $params = [$user_id];
} elseif ($filter === 'mine') {
    // Của tôi: CHỈ hiển thị bài do chính user này tạo
    $sql = $sql_base . " WHERE q.created_by = ? ORDER BY q.quiz_id DESC";
    $params = [$user_id];
} elseif ($filter === 'system') {
    // Của hệ thống: CHỈ hiển thị bài do Admin tạo
    $sql = $sql_base . " WHERE (u.role = 'admin' OR u.role IS NULL) ORDER BY q.quiz_id DESC";
    $params = []; // Không cần truyền $user_id
} else {
    // Mới nhất (Mặc định): Sắp xếp mới -> cũ (DESC)
    $sql = $sql_base . " WHERE (q.created_by = ? OR u.role = 'admin' OR u.role IS NULL) ORDER BY q.quiz_id DESC";
    $params = [$user_id];
}

// Thực thi SQL
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
    <style>
        .active-filter {
            background: var(--primary-color, #3498db) !important;
            color: white !important;
            border: 2px solid var(--primary-color, #3498db);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .inactive-filter {
            background: white !important;
            color: #555 !important;
            border: 2px solid #ddd;
            transition: all 0.3s ease;
        }
        .inactive-filter:hover {
            background: #f8f9fa !important;
            border-color: #ccc;
        }
    </style>
    
<div >
    <h2 style="color: var(--primary-color); border-bottom: 2px solid #eee; padding-bottom: 10px;">
        <i class="fas fa-list-ul"></i> Danh sách Bài kiểm tra
    </h2>
    <p style="margin-bottom: 25px; color: #666;">Chọn một bài thi để bắt đầu làm.</p>

    <a href="../../user/history.php" class="btn" style="background: #555; margin-bottom: 20px; display: inline-block;">
        <i class="fas fa-history"></i> Xem lịch sử điểm thi
    </a>
    <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
        <a href="?filter=newest" class="btn <?= $filter == 'newest' ? 'active-filter' : 'inactive-filter' ?>">
            <i class="fas fa-sort-amount-down"></i> Mới nhất
        </a>
        <a href="?filter=oldest" class="btn <?= $filter == 'oldest' ? 'active-filter' : 'inactive-filter' ?>">
            <i class="fas fa-sort-amount-up"></i> Cũ nhất
        </a>
        <a href="?filter=mine" class="btn <?= $filter == 'mine' ? 'active-filter' : 'inactive-filter' ?>">
            <i class="fas fa-user"></i> Của tôi
        </a>
        <a href="?filter=system" class="btn <?= $filter == 'system' ? 'active-filter' : 'inactive-filter' ?>">
            <i class="fas fa-server"></i> Của hệ thống
        </a>
    </div>
    <?php if (empty($quizzes)): ?>
        <div style="text-align: center; padding: 40px; background: #fff; border-radius: 8px;">
            <p>Hiện chưa có bài thi nào phù hợp với bạn.</p>
            <a href="create_quiz_ai.php" class="btn">Tạo bài thi mới ngay</a>
        </div>
    <?php else: ?>
        <div class="quiz-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
            
            <div class="quiz-card" style="background: #f9f9f9; padding: 20px; border-radius: 10px; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; min-height: 200px;">
                <div style="text-align: center;">
                        <a href="create_quiz_ai.php" style="text-decoration: none; color: var(--primary-color);">
                            <i class="fas fa-plus-circle" style="font-size: 3rem; margin-bottom: 10px;"></i>
                            <h3 style="margin: 0;">Tạo đề mới</h3>
                            <p style="color: #666; font-size: 0.9rem;">(Còn <?= $current_credits ?> Credits)</p>
                        </a>
                </div>
            </div>

            <?php foreach ($quizzes as $q): ?>
                <?php 
                    // Đếm số câu hỏi
                    $stmt_count = $conn->prepare("SELECT COUNT(*) FROM questions WHERE quiz_id = ?");
                    $stmt_count->execute([$q['quiz_id']]);
                    $q_count = $stmt_count->fetchColumn();
                    
                    // Xử lý tên người tạo để hiển thị
                    $creator_name = htmlspecialchars($q['username'] ?? 'Hệ thống'); // Nếu ko có tên thì là Hệ thống
                    $is_admin_post = (stripos($q['role'] ?? '', 'admin') !== false) || empty($q['role']);
                    $is_my_post = ($q['created_by'] == $user_id);
                ?>
                
                <div class="quiz-card" style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: transform 0.2s; border: 1px solid #eee;">
                    <h3 style="margin-top: 0; font-size: 1.1rem; color: #333; height: 50px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical;">
                        <?= htmlspecialchars($q['title']) ?>
                    </h3>

                    <div style="margin: 15px 0; font-size: 0.9rem; color: #777;">
                        <p><i class="fas fa-question-circle"></i> Số câu hỏi: <b><?= $q_count ?></b></p>
                        
                        <p>
                            <i class="fas fa-user"></i> Tạo bởi: 
                            <?php if($is_admin_post): ?>
                                <span style="color: #e74c3c; font-weight: bold;">Hệ thống</span>
                            <?php elseif($is_my_post): ?>
                                <span style="color: #27ae60; font-weight: bold;">Của tôi</span>
                            <?php else: ?>
                                <?= $creator_name ?>
                            <?php endif; ?>
                        </p>
                    </div>

                    <div style="text-align: center;">
                        <a href="quiz.php?quiz_id=<?= $q['quiz_id'] ?>" class="btn" style="width: 100%; display: block;">
                            Bắt đầu làm bài
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .quiz-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.1) !important;
        border-color: var(--primary-color) !important;
    }
</style>

<?php include '../../includes/footer.php'; ?>