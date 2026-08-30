<?php
require '../config/db.php';
include '../includes/header.php';

// Nếu không phải POST thì quay lại
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: quiz_list.php");
    exit;
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$quiz_id = intval($_POST['quiz_id']);
$answers = $_POST['ans'] ?? [];

$score = 0;
$correct_count = 0;
$wrong_count = 0;
$skip_count = 0;
$details = [];

// nhận mảng thứ tự từ quiz.php gửi sang
$question_order = $_POST['question_order'] ?? [];

// Lấy câu hỏi từ DB
$stmt = $conn->prepare("SELECT * FROM questions WHERE quiz_id = ?");
$stmt->execute([$quiz_id]);
$db_questions_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

// [MỚI] Gom nhóm câu hỏi lại với Key là question_id để dễ dàng tra cứu
$db_questions = [];
foreach ($db_questions_raw as $row) {
    $db_questions[$row['question_id']] = $row;
}

// Tổng số câu hỏi dựa trên mảng thứ tự
$total_questions = count($question_order);

// ======================
// CHẤM ĐIỂM
// ======================
// Duyệt qua mảng thứ tự thay vì duyệt theo Database
foreach ($question_order as $qid) {
    // Tránh lỗi nếu ID câu hỏi không tồn tại trong DB
    if (!isset($db_questions[$qid])) continue; 
    
    // Lấy chi tiết câu hỏi ra
    $q = $db_questions[$qid]; 

    // --- TỪ ĐOẠN NÀY TRỞ ĐI LÀ CODE CŨ CỦA BẠN ---
    $user_choice = isset($answers[$qid]) && $answers[$qid] !== ''
        ? strtoupper(trim($answers[$qid]))
        : null;

    $correct_ans = strtoupper(trim($q['correct_answer']));

    if ($user_choice === null) {
        $status = 'skip';
        $skip_count++;
    } elseif ($user_choice === $correct_ans) {
        $status = 'correct';
        $correct_count++;
        $score += 1; 
    } else {
        $status = 'wrong';
        $wrong_count++;
    }

    $details[] = [
        'question' => $q,
        'user_choice' => $user_choice,
        'correct_ans' => $correct_ans,
        'status' => $status
    ];
}

// Điểm hệ 10
$final_score = $total_questions > 0
    ? round(($score / $total_questions) * 10, 2)
    : 0;

// Lưu kết quả
$stmt = $conn->prepare("
    INSERT INTO test_results (user_id, quiz_id, score, created_at)
    VALUES (?, ?, ?, NOW())
");
$stmt->execute([$user_id, $quiz_id, $final_score]);
?>

<style>
    .result-header {
        text-align: center;
        background: #fff;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }

    .score-circle {
        width: 130px;
        height: 130px;
        background: #3498db;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        font-weight: bold;
        margin: 0 auto 20px;
    }

    .stat-box {
        display: flex;
        justify-content: center;
        gap: 40px;
        margin-bottom: 20px;
    }

    .stat-item {
        text-align: center;
    }

    .stat-item b {
        display: block;
        font-size: 1.5rem;
    }

    .question-review {
        background: #fff;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        border-left: 6px solid #ccc;
    }

    .question-review.correct { border-left-color: #2ecc71; }
    .question-review.wrong   { border-left-color: #e74c3c; }
    .question-review.skip    { border-left-color: #95a5a6; }

    .option-row {
        padding: 10px 15px;
        margin: 6px 0;
        border-radius: 6px;
        background: #f9f9f9;
        border: 1px solid #eee;
    }

    .bg-correct {
        background: #d4edda !important;
        border-color: #c3e6cb !important;
        color: #155724;
        font-weight: bold;
    }

    .bg-wrong {
        background: #f8d7da !important;
        border-color: #f5c6cb !important;
        color: #721c24;
        text-decoration: line-through;
    }
</style>

<div >

<div class="result-header">
    <div class="score-circle"><?= $final_score ?></div>
    <h2>Kết quả bài thi</h2>

    <div class="stat-box">
        <div class="stat-item" style="color:#2ecc71;">
            <b><?= $correct_count ?></b> Đúng
        </div>
        <div class="stat-item" style="color:#e74c3c;">
            <b><?= $wrong_count ?></b> Sai
        </div>
        <div class="stat-item" style="color:#95a5a6;">
            <b><?= $skip_count ?></b> Bỏ qua
        </div>
    </div>

    <a href="../modules/quiz/quiz_list.php" class="btn">Làm bài khác</a>
    <a href="../user/history.php" class="btn" style="background:#555;">Xem lịch sử</a>
</div>

<h3>Xem lại bài làm:</h3>

<?php foreach ($details as $index => $d): ?>

<div class="question-review <?= $d['status'] ?>">

<p style="font-weight:bold;">
Câu <?= $index+1 ?>:
<?= $d['question']['content'] ?>

<?php if ($d['status'] === 'correct'): ?>
<span style="float:right;color:#2ecc71;">
<i class="fas fa-check-circle"></i> Chính xác
</span>

<?php elseif ($d['status'] === 'skip'): ?>
<span style="float:right;color:#95a5a6;">
<i class="fas fa-minus-circle"></i> Bỏ qua
</span>

<?php else: ?>
<span style="float:right;color:#e74c3c;">
<i class="fas fa-times-circle"></i> Sai rồi
</span>
<?php endif; ?>
</p>

<?php
$options = ['A','B','C','D'];
foreach ($options as $opt):

    $opt_text = $d['question']['option_'.strtolower($opt)];
    $class = '';
    $extra = '';

    if ($d['correct_ans'] === $opt) {
        $class = 'bg-correct';

        if ($d['user_choice'] === $opt) {
            $extra = " <i class='fas fa-check'></i> (Bạn chọn)";
        } else {
            $extra = " <i class='fas fa-check'></i> (Đáp án đúng)";
        }
    }
    elseif ($d['user_choice'] === $opt) {
        $class = 'bg-wrong';
        $extra = " <i class='fas fa-times'></i> (Bạn chọn sai)";
    }
?>

<div class="option-row <?= $class ?>">
    <b><?= $opt ?>.</b>
    <?= htmlspecialchars($opt_text) ?>
    <?= $extra ?>
</div>

<?php endforeach; ?>

</div>

<?php endforeach; ?>

</div>

<?php include '../includes/footer.php'; ?>
