<?php
require '../../config/db.php';
include '../../includes/header.php';

// Check Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { header("Location: ../auth/login.php"); exit; }

// 1. Kiểm tra ID hợp lệ
if (!isset($_GET['id'])) {
    echo "<div class='container'><p style='color:red; text-align:center;'>Không tìm thấy ID bài thi!</p></div>";
    include '../../includes/footer.php'; exit;
}
$id = $_GET['id'];

// 2. Lấy thông tin bài thi hiện tại
$stmt = $conn->prepare("SELECT * FROM quizzes WHERE quiz_id = ?");
$stmt->execute([$id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    echo "<div class='container'><p style='color:red; text-align:center;'>Bài thi không tồn tại hoặc đã bị xóa.</p></div>";
    include '../../includes/footer.php'; exit;
}

// 3. XỬ LÝ CẬP NHẬT
if (isset($_POST['update_quiz'])) {
    $title = trim($_POST['title']);
    
    if (!empty($title)) {
        $stmt = $conn->prepare("UPDATE quizzes SET title = ? WHERE quiz_id = ?");
        $stmt->execute([$title, $id]);
        
        // Thông báo và chuyển hướng
        echo "<script>
                alert('Cập nhật thành công!'); 
                window.location.href='manage_quizzes.php';
              </script>";
    } else {
        $error = "Tên bài thi không được để trống!";
    }
}
?>

<div >
    <div style="max-width: 600px; margin: 0 auto;">
        <h2>Chỉnh sửa Bài thi</h2>
        
        <div style="background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
            
            <?php if(isset($error)): ?>
                <p style="color: red; background: #ffe6e6; padding: 10px; border-radius: 5px;"><?= $error ?></p>
            <?php endif; ?>

            <form method="POST">
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Tên bài thi:</label>
                    <input type="text" name="title" 
                           value="<?= htmlspecialchars($quiz['title']) ?>" 
                           required 
                           style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 1rem;">
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <a href="manage_quizzes.php" class="btn" style="background: #777; color: white; text-decoration: none;">Hủy bỏ</a>
                    <button type="submit" name="update_quiz" class="btn" style="background: var(--primary-color); color: white;">
                        <i class="fas fa-save"></i> Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>