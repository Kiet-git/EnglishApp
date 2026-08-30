<?php
require '../../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);

// XỬ LÝ CẬP NHẬT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $title = trim($_POST['title']);
    $level = trim($_POST['level']);
    $content = trim($_POST['content']);
    $content_vi = trim($_POST['content_vi']);
    
    // Lấy chuỗi JSON người dùng sửa
    $vocab_data = $_POST['vocab_data'];
    $quiz_data = $_POST['quiz_data'];

    // Kiểm tra xem JSON có bị lỗi cú pháp không trước khi lưu
    if (json_decode($vocab_data) === null || json_decode($quiz_data) === null) {
        $error = "Lỗi: Cấu trúc JSON của Từ vựng hoặc Trắc nghiệm không hợp lệ. Vui lòng kiểm tra lại dấu ngoặc hoặc dấu phẩy!";
    } else {
        $stmt = $conn->prepare("UPDATE readings SET title=?, level=?, content=?, content_vi=?, vocab_data=?, quiz_data=? WHERE id=?");
        if ($stmt->execute([$title, $level, $content, $content_vi, $vocab_data, $quiz_data, $id])) {
            echo "<script>alert('Cập nhật thành công!'); window.location='manage_readings.php';</script>";
            exit;
        } else {
            $error = "Có lỗi xảy ra khi lưu vào Database.";
        }
    }
}

// LẤY DỮ LIỆU BÀI ĐỌC HIỆN TẠI
$stmt = $conn->prepare("SELECT * FROM readings WHERE id = ?");
$stmt->execute([$id]);
$reading = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reading) {
    die("<script>alert('Không tìm thấy bài đọc!'); window.location='manage_readings.php';</script>");
}

// Format lại JSON cho đẹp để admin dễ sửa
$vocab_pretty = json_encode(json_decode($reading['vocab_data']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$quiz_pretty = json_encode(json_decode($reading['quiz_data']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

include '../../includes/header.php';
?>

<div  style="margin-top: 30px;">
    <h2><i class="fas fa-edit"></i> Chỉnh Sửa Bài Đọc #<?= $reading['id'] ?></h2>
    
    <?php if (isset($error)): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: bold;">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST" style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-top: 5px solid #3498db;">
        
        <div style="margin-bottom: 15px;">
            <label style="font-weight: bold;">Tiêu đề bài đọc:</label>
            <input type="text" name="title" value="<?= htmlspecialchars($reading['title']) ?>" required style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="font-weight: bold;">Cấp độ:</label>
            <select name="level" required style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                <?php foreach (['A1', 'A2', 'B1', 'B2', 'C1'] as $lvl): ?>
                    <option value="<?= $lvl ?>" <?= ($reading['level'] == $lvl) ? 'selected' : '' ?>><?= $lvl ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="font-weight: bold;">Nội dung tiếng Anh:</label>
            <textarea name="content" rows="6" required style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc; font-family: sans-serif;"><?= htmlspecialchars($reading['content']) ?></textarea>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="font-weight: bold;">Bản dịch tiếng Việt:</label>
            <textarea name="content_vi" rows="6" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc; font-family: sans-serif;"><?= htmlspecialchars($reading['content_vi'] ?? '') ?></textarea>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="font-weight: bold; color: #d35400;">Dữ liệu Từ vựng (Định dạng JSON - Cẩn thận khi sửa):</label>
            <textarea name="vocab_data" rows="10" required style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc; font-family: monospace; background: #f8f9fa;"><?= htmlspecialchars($vocab_pretty) ?></textarea>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="font-weight: bold; color: #d35400;">Dữ liệu Trắc nghiệm (Định dạng JSON - Cẩn thận khi sửa):</label>
            <textarea name="quiz_data" rows="10" required style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc; font-family: monospace; background: #f8f9fa;"><?= htmlspecialchars($quiz_pretty) ?></textarea>
        </div>

        <div style="display: flex; gap: 15px;">
            <button type="submit" name="update" style="flex: 1; background: #3498db; color: white; padding: 12px; border: none; border-radius: 5px; font-size: 1.1rem; cursor: pointer; font-weight: bold;">
                <i class="fas fa-save"></i> Lưu Thay Đổi
            </button>
            <a href="manage_readings.php" style="flex: 1; background: #95a5a6; color: white; padding: 12px; border: none; border-radius: 5px; font-size: 1.1rem; text-align: center; text-decoration: none; font-weight: bold;">
                Hủy bỏ
            </a>
        </div>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>