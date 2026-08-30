<?php
require '../../config/db.php';
require '../../includes/pagination_helper.php'; // thư viện phân trang

if (session_status() === PHP_SESSION_NONE) session_start();

// Check Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

// 1. Kiểm tra tham số quiz_id
if (!isset($_GET['quiz_id'])) {
    die("<div class='container'><p style='color:red; text-align:center;'>Vui lòng chọn bài thi trước!</p></div>");
}

$quiz_id = $_GET['quiz_id'];

// 2. Lấy tên bài thi
$stmt = $conn->prepare("SELECT title FROM quizzes WHERE quiz_id = ?");
$stmt->execute([$quiz_id]);
$quiz_title = $stmt->fetchColumn();

if (!$quiz_title) {
    die("<div class='container'><p style='color:red;'>Bài thi không tồn tại.</p></div>");
}


// --- 3. THÊM CÂU HỎI ---
if (isset($_POST['add_question'])) {

    $content = $_POST['content'];
    $opt_a = $_POST['option_a'];
    $opt_b = $_POST['option_b'];
    $opt_c = $_POST['option_c'];
    $opt_d = $_POST['option_d'];
    $correct = $_POST['correct_answer'];

    if (!empty($content) && !empty($correct)) {

        $sql = "INSERT INTO questions 
                (quiz_id, content, option_a, option_b, option_c, option_d, correct_answer) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$quiz_id, $content, $opt_a, $opt_b, $opt_c, $opt_d, $correct]);

        header("Location: manage_questions.php?quiz_id=$quiz_id");
        exit;
    }
}


// --- 4. XÓA CÂU HỎI ---
if (isset($_GET['del_id'])) {

    $conn->prepare("DELETE FROM questions WHERE question_id = ?")
         ->execute([$_GET['del_id']]);

    header("Location: manage_questions.php?quiz_id=$quiz_id");
    exit;
}


// --- 5. PHÂN TRANG ---
$sql_count = "SELECT COUNT(*) FROM questions WHERE quiz_id = ?";
$sql_data  = "SELECT * FROM questions WHERE quiz_id = ? ORDER BY question_id ASC";

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;

$paging_result = getPagingData($conn, $sql_count, $sql_data, [$quiz_id], $limit, $page);

$questions = $paging_result['data'];
$total_pages = $paging_result['total_pages'];
$current_page = $paging_result['current_page'];


include '../../includes/header.php';
?>

<div >
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h2 style="margin-bottom: 5px; color: var(--primary-color);">Quản lý Câu hỏi</h2>
            <p style="color: #666; margin: 0;">Bài thi: <b style="color: var(--primary-color);"><?= htmlspecialchars($quiz_title) ?></b></p>
        </div>
        <a href="manage_quizzes.php" class="btn" style="background: #777;">&larr; Quay lại DS Bài thi</a>
    </div>

    <div style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
        <h4 style="margin-top: 0; color: var(--primary-color); border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">
            <i class="fas fa-plus-circle"></i> Thêm câu hỏi mới
        </h4>
        
        <form method="POST">
            <div style="margin-bottom: 15px;">
                <label style="font-weight: bold;">Nội dung câu hỏi <span style="color:red">*</span></label>
                <input type="text" name="content" required style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Ví dụ: Con chó trong tiếng Anh là gì?">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                <div>
                    <label>Đáp án A:</label>
                    <input type="text" name="option_a" required style="width: 100%; margin-top:5px;">
                </div>
                <div>
                    <label>Đáp án B:</label>
                    <input type="text" name="option_b" required style="width: 100%; margin-top:5px;">
                </div>
                <div>
                    <label>Đáp án C:</label>
                    <input type="text" name="option_c" required style="width: 100%; margin-top:5px;">
                </div>
                <div>
                    <label>Đáp án D:</label>
                    <input type="text" name="option_d" required style="width: 100%; margin-top:5px;">
                </div>
            </div>

            <div style="display: flex; gap: 20px; align-items: flex-end;">
                <div style="flex: 1;">
                    <label style="font-weight: bold; color: #e74c3c;">Đáp án đúng:</label>
                    <select name="correct_answer" required style="width: 100%; padding: 10px; margin-top: 5px; font-weight: bold;">
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                    </select>
                </div>
                <div style="flex: 2; text-align: right;">
                    <button type="submit" name="add_question" class="btn" style="padding: 10px 30px;background:var(--success-color)">
                        <i class="fas fa-save"></i> Lưu câu hỏi
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div style="background: #fff; padding: 10px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 40%;">Câu hỏi & Đáp án</th>
                    <th style="width: 15%; text-align: center;">Đáp án đúng</th>
                    <th style="width: 15%; text-align: center;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($questions) == 0): ?>
                    <tr><td colspan="4" style="text-align: center; padding: 20px; color: #888;">Chưa có câu hỏi nào.</td></tr>
                <?php else: ?>
                    <?php foreach ($questions as $index => $q): ?>
                    <tr>
                        <td><b><?= ($current_page - 1) * $limit + $index + 1 ?></b></td>
                        <td>
                            <div style="font-weight: bold; margin-bottom: 5px;"><?= $q['content'] ?></div>
                            <div style="font-size: 0.9rem; color: #666; display: grid; grid-template-columns: 1fr 1fr; gap: 5px;">
                                <span>A. <?= htmlspecialchars($q['option_a']) ?></span>
                                <span>B. <?= htmlspecialchars($q['option_b']) ?></span>
                                <span>C. <?= htmlspecialchars($q['option_c']) ?></span>
                                <span>D. <?= htmlspecialchars($q['option_d']) ?></span>
                            </div>
                        </td>
                        <td style="text-align: center;">
                            <span style="background: #2ecc71; color: #fff; padding: 5px 12px; border-radius: 20px; font-weight: bold;">
                                <?= $q['correct_answer'] ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <a href="edit_question.php?id=<?= $q['question_id'] ?>" class="btn btn-warning" style="padding: 6px 12px;"><i class="fas fa-edit"></i></a>
                            
                            <a href="manage_questions.php?quiz_id=<?= $quiz_id ?>&del_id=<?= $q['question_id'] ?>" 
                               class="btn btn-danger" 
                               style="padding: 6px 12px;" 
                               onclick="return confirm('Xóa câu hỏi này?')">
                               <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php renderPagingUI($total_pages, $current_page); ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>