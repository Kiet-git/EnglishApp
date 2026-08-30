<?php
require '../../config/db.php';

// Kiểm tra session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

// 1. LẤY ID BÀI THI TỪ URL
// Phải có quiz_id thì mới biết làm bài nào
if (!isset($_GET['quiz_id'])) {
    echo "<script>alert('Không tìm thấy bài thi!'); window.location.href='../../index.php';</script>";
    exit;
}
$quiz_id = (int)$_GET['quiz_id'];
$user_id = $_SESSION['user_id'];

// 2. LẤY THÔNG TIN BÀI THI + VAI TRÒ NGƯỜI TẠO
// Join bảng users để biết ai tạo bài này và họ có quyền gì (admin hay student)
$sql = "SELECT q.*, u.role as creator_role 
        FROM quizzes q
        JOIN users u ON q.created_by = u.user_id
        WHERE q.quiz_id = ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch(); // Chỉ lấy 1 dòng (fetch) chứ không lấy danh sách (fetchAll)

// Nếu không tìm thấy bài thi
if (!$quiz) {
    include '../../includes/header.php';
    echo "<div class='container' style='text-align:center; padding:50px;'>
            <h3>Bài thi không tồn tại hoặc đã bị xóa!</h3>
            <a href='user/my_quizzes.php' class='btn'>Quay lại</a>
          </div>";
    include '../../includes/footer.php';
    exit;
}

// 3. LOGIC BẢO MẬT (QUAN TRỌNG)
// - Admin tạo: Ai cũng làm được (Public).
// - Student tạo: Chỉ chính chủ mới làm được (Private).
$creator_id = $quiz['created_by'];
$creator_role = $quiz['creator_role'];

if ($creator_role != 'admin' && $creator_id != $user_id) {
    // Nếu không phải admin và không phải bài của mình -> ĐÁ VỀ KHO CỦA TÔI
    header("Location: my_quizzes.php"); 
    exit; 
}

// --- NẾU QUA ĐƯỢC CÁC BƯỚC TRÊN THÌ MỚI LOAD HEADER VÀ GIAO DIỆN ---
include '../../includes/header.php';

// 4. LẤY DANH SÁCH CÂU HỎI
$stmt = $conn->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY RAND()");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();
$total_questions = count($questions);
?>

<style>
    #quiz-progress {
        position: fixed; bottom: 20px; right: 20px; background: #fff; padding: 15px 20px;
        border-radius: 50px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 1000;
        display: flex; align-items: center; gap: 15px; border: 2px solid var(--primary-color, #3498db);
        animation: slideInRight 0.5s ease-out;
    }
    @keyframes slideInRight { from { transform: translateX(100px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    .progress-text { font-weight: bold; color: #333; font-size: 1.1rem; }
    .progress-highlight { color: var(--primary-color, #3498db); font-size: 1.3rem; }
    .progress-circle { width: 24px; height: 24px; border-radius: 50%; background: conic-gradient(var(--primary-color, #3498db) 0%, #eee 0%); transition: background 0.3s ease; }
    .quiz-item label { cursor: pointer; display: block; padding: 8px; border-radius: 5px; transition: background 0.2s; }
    .quiz-item label:hover { background: #f0f8ff; }
    .quiz-item input[type="radio"]:checked + span { font-weight: bold; color: var(--primary-color); }
</style>

<div >
    <h2>Làm bài: <span style="color: var(--primary-color);"><?= htmlspecialchars($quiz['title']) ?></span></h2>
    
    <?php if ($total_questions == 0): ?>
        <div style="text-align: center; padding: 50px;">
            <p>Bài thi này chưa có câu hỏi nào.</p>
            <a href="quiz_list.php" class="btn">Quay lại</a>
        </div>
    <?php else: ?>

    <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px; color: #856404; border-left: 5px solid #ffeeba;">
        <i class="fas fa-exclamation-triangle"></i> Lưu ý: Hãy chọn đáp án chính xác nhất. Chúc bạn làm bài tốt!
    </div>

    <form action="../../user/result.php" method="POST" id="quizForm">
        <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
        
        <?php foreach($questions as $index => $q): ?>
            <div class="quiz-item" style="background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <p style="font-size: 1.1rem; margin-bottom: 15px;">
                    <b style="color: var(--primary-color);">Câu <?= $index + 1 ?>:</b> <?= $q['content'] ?> 
                </p>
                <input type="hidden" name="question_order[]" value="<?= $q['question_id'] ?>">
                
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label><input type="radio" name="ans[<?= $q['question_id'] ?>]" value="A" onchange="updateProgress()"> <span>A. <?= htmlspecialchars($q['option_a']) ?></span></label>
                    <label><input type="radio" name="ans[<?= $q['question_id'] ?>]" value="B" onchange="updateProgress()"> <span>B. <?= htmlspecialchars($q['option_b']) ?></span></label>
                    <label><input type="radio" name="ans[<?= $q['question_id'] ?>]" value="C" onchange="updateProgress()"> <span>C. <?= htmlspecialchars($q['option_c']) ?></span></label>
                    <label><input type="radio" name="ans[<?= $q['question_id'] ?>]" value="D" onchange="updateProgress()"> <span>D. <?= htmlspecialchars($q['option_d']) ?></span></label>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div style="text-align: center; margin-top: 30px; margin-bottom: 50px;">
            <button type="submit" class="btn" style="font-size: 1.2rem; padding: 15px 50px; background: #27ae60;" onclick="return confirm('Bạn có chắc chắn muốn NỘP BÀI?')">
                <i class="fas fa-check-circle"></i> NỘP BÀI THI
            </button>
        </div>
    </form>

    <div id="quiz-progress">
        <div class="progress-circle" id="progressCircle"></div>
        <div class="progress-text">
            Đã làm: <span id="doneCount" class="progress-highlight">0</span> / <span id="totalCount"><?= $total_questions ?></span>
        </div>
    </div>
    
    <?php endif; // Kết thúc if check total_questions ?>
</div>

<script>
    const totalQuestions = <?= $total_questions ?>;

    function updateProgress() {
        if (totalQuestions === 0) return;

        const answeredCount = document.querySelectorAll('input[type="radio"]:checked').length;
        document.getElementById('doneCount').innerText = answeredCount;

        const percentage = (answeredCount / totalQuestions) * 100;
        const circle = document.getElementById('progressCircle');
        circle.style.background = `conic-gradient(var(--primary-color, #3498db) ${percentage}%, #eee ${percentage}%)`;
        
        if (answeredCount === totalQuestions) {
            document.getElementById('quiz-progress').style.borderColor = '#2ecc71';
            document.getElementById('progressCircle').style.background = '#2ecc71';
        }
    }
    window.onload = updateProgress;
</script>

<?php include '../../includes/footer.php'; ?>