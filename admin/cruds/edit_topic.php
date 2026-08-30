<?php
require '../../config/db.php';
include '../../includes/header.php';

// Check Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { header("Location: ../auth/login.php"); exit; }

// Lấy ID
$id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("SELECT * FROM topics WHERE topic_id = ?");
$stmt->execute([$id]);
$topic = $stmt->fetch();

if (!$topic) { header("Location: manage_topics.php"); exit; }

// Xử lý Lưu
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['topic_name'];
    $desc = $_POST['description'];
    $image = $topic['image']; // Giữ nguyên ảnh cũ mặc định

    // Nếu có chọn ảnh mới thì upload và lấy tên mới
    if (isset($_FILES['topic_image']) && $_FILES['topic_image']['size'] > 0) {
        $target_dir = "../../uploads/";
        // Kiểm tra và tạo thư mục nếu chưa có
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

        $file_name = time() . "_" . basename($_FILES["topic_image"]["name"]);
        $target_file = $target_dir . $file_name;
        
        // Chỉ chấp nhận file ảnh
        $check = getimagesize($_FILES["topic_image"]["tmp_name"]);
        if($check !== false) {
            if (move_uploaded_file($_FILES["topic_image"]["tmp_name"], $target_file)) {
                // (Tùy chọn) Xóa ảnh cũ đi để đỡ rác server
                if ($image && $image != 'default.png' && file_exists("../../uploads/$image")) {
                    unlink("../../uploads/$image");
                }
                $image = $file_name;
            }
        }
    }
    
    $sql = "UPDATE topics SET topic_name = ?, description = ?, image = ? WHERE topic_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$name, $desc, $image, $id]);
    
    echo "<script>alert('Cập nhật thành công!'); window.location.href='manage_topics.php';</script>";
}
?>

<div >
    <h2>Sửa Chủ đề: <span style="color:var(--primary-color)"><?= htmlspecialchars($topic['topic_name']) ?></span></h2>
    <a href="manage_topics.php" class="btn" style="background:#777; margin-bottom:20px;">Quay lại</a>

    <div class="auth-box" style="max-width: 600px; margin: 0 auto;">
        <form method="POST" enctype="multipart/form-data">
            
            <label style="display:block; text-align:left; font-weight:bold;">Tên chủ đề:</label>
            <input type="text" name="topic_name" value="<?= htmlspecialchars($topic['topic_name']) ?>" required>
            
            <label style="display:block; text-align:left; font-weight:bold;">Mô tả:</label>
            <input type="text" name="description" value="<?= htmlspecialchars($topic['description']) ?>">
            
            <label style="display:block; text-align:left; font-weight:bold;">Ảnh minh họa:</label>
            
            <div style="display: flex; gap: 20px; align-items: center; background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #ddd;">
                <div style="width: 120px; height: 120px; flex-shrink: 0; border: 2px dashed #ccc; border-radius: 8px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #fff;">
                    <?php 
                        // Kiểm tra nếu có ảnh trong DB thì hiện, không thì hiện ảnh default hoặc placeholder
                        $imgSrc = (!empty($topic['image']) && file_exists("../../uploads/" . $topic['image'])) 
                                  ? "../../uploads/" . $topic['image'] 
                                  : "https://via.placeholder.com/150?text=No+Image"; 
                    ?>
                    <img id="img-preview" src="<?= $imgSrc ?>" style="width: 100%; height: 100%; object-fit: cover;">
                </div>

                <div style="flex-grow: 1;">
                    <label class="file-label">
                    <p style="font-size: 0.9rem; color: #666; margin-bottom: 5px;">Chọn ảnh mới để thay thế ảnh cũ:</p>
                    <input style="display: none;" type="file" name="topic_image" onchange="previewImage(this)" accept="image/*" style="width: 100%;">
                    <span class="file-btn">📂 Chọn file</span>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn btn-warning" style="width:100%; margin-top: 20px;">LƯU THAY ĐỔI</button>
        </form>
    </div>
</div>

<script>
    function previewImage(input) {
        var preview = document.getElementById('img-preview');
        
        // Nếu người dùng có chọn file
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function (e) {
                preview.src = e.target.result; // Gán data ảnh mới vào thẻ img
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<?php include '../../includes/footer.php'; ?>