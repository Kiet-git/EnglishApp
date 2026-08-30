<?php
require '../../config/db.php';
include '../../includes/header.php';

// Check Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { header("Location: ../../auth/login.php"); exit; }

// 1. Kiểm tra ID câu hỏi
if (!isset($_GET['id'])) {
    echo "<div class='container'><p style='color:red;'>Không tìm thấy ID câu hỏi!</p></div>";
    include '../../includes/footer.php'; exit;
}
$id = $_GET['id'];

// 2. Lấy thông tin câu hỏi hiện tại
$stmt = $conn->prepare("SELECT * FROM questions WHERE question_id = ?");
$stmt->execute([$id]);
$question = $stmt->fetch();

if (!$question) {
    echo "<div class='container'><p style='color:red;'>Câu hỏi không tồn tại.</p></div>";
    include '../../includes/footer.php'; exit;
}

// Lấy quiz_id để lát nữa quay lại đúng trang danh sách
$quiz_id = $question['quiz_id'];

// 3. XỬ LÝ LƯU CẬP NHẬT
if (isset($_POST['update_question'])) {
    $content = $_POST['content'];
    $opt_a = $_POST['option_a'];
    $opt_b = $_POST['option_b'];
    $opt_c = $_POST['option_c'];
    $opt_d = $_POST['option_d'];
    $correct = $_POST['correct_answer'];

    if (!empty($content)) {
        $sql = "UPDATE questions SET 
                content = ?, 
                option_a = ?, 
                option_b = ?, 
                option_c = ?, 
                option_d = ?, 
                correct_answer = ? 
                WHERE question_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$content, $opt_a, $opt_b, $opt_c, $opt_d, $correct, $id]);

        echo "<script>
                alert('Cập nhật thành công!'); 
                window.location.href='manage_questions.php?quiz_id=$quiz_id';
              </script>";
    }
}
?>

<div >
    <h2>Sửa Câu Hỏi</h2>
    <a href="manage_questions.php?quiz_id=<?= $quiz_id ?>" class="btn" style="background:#777; margin-bottom: 20px;">
        &larr; Quay lại danh sách
    </a>

    <div style="background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
        
        <form method="POST">
            <div style="margin-bottom: 20px;">
                <label style="font-weight: bold; font-size: 1.1rem; color: var(--primary-color);">Nội dung câu hỏi:</label>
                <textarea name="content" rows="2" required style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 5px; font-size: 1rem;"><?= htmlspecialchars($question['content']) ?></textarea>
            </div>

            <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 20px;">
                
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <div>
                        <label style="font-weight: bold;">Đáp án A:</label>
                        <input type="text" name="option_a" value="<?= htmlspecialchars($question['option_a']) ?>" required style="width: 100%; padding: 8px;">
                    </div>
                    <div>
                        <label style="font-weight: bold;">Đáp án C:</label>
                        <input type="text" name="option_c" value="<?= htmlspecialchars($question['option_c']) ?>" required style="width: 100%; padding: 8px;">
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <div>
                        <label style="font-weight: bold;">Đáp án B:</label>
                        <input type="text" name="option_b" value="<?= htmlspecialchars($question['option_b']) ?>" required style="width: 100%; padding: 8px;">
                    </div>
                    <div>
                        <label style="font-weight: bold;">Đáp án D:</label>
                        <input type="text" name="option_d" value="<?= htmlspecialchars($question['option_d']) ?>" required style="width: 100%; padding: 8px;">
                    </div>
                </div>
            </div>

            <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; border-left: 5px solid #e74c3c; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <label style="font-weight: bold; color: #e74c3c; font-size: 1rem;">Đáp án ĐÚNG hiện tại:</label>
                    <select name="correct_answer" required style="padding: 8px 20px; font-weight: bold; border: 2px solid #e74c3c; border-radius: 5px; margin-left: 10px;">
                        <option value="A" <?= $question['correct_answer'] == 'A' ? 'selected' : '' ?>>A</option>
                        <option value="B" <?= $question['correct_answer'] == 'B' ? 'selected' : '' ?>>B</option>
                        <option value="C" <?= $question['correct_answer'] == 'C' ? 'selected' : '' ?>>C</option>
                        <option value="D" <?= $question['correct_answer'] == 'D' ? 'selected' : '' ?>>D</option>
                    </select>
                </div>

                <button type="submit" name="update_question" class="btn btn-warning" style="padding: 10px 40px; font-weight: bold;">
                    <i class="fas fa-check"></i> CẬP NHẬT
                </button>
            </div>

        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>