<?php
require '../../config/db.php';
require '../../includes/pagination_helper.php';

// Xử lý Thêm Chủ đề
if (isset($_POST['add_topic'])) {
    $name = $_POST['topic_name'];
    $desc = $_POST['description'];
    $image = ""; // Mặc định không có ảnh

    // Xử lý Upload ảnh
    if (isset($_FILES['topic_image']) && $_FILES['topic_image']['error'] == 0) {
        $target_dir = "../../uploads/";
        // Kiểm tra thư mục uploads có tồn tại chưa, nếu chưa thì tạo
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES["topic_image"]["name"], PATHINFO_EXTENSION);
        $file_name = time() . "_" . uniqid() . "." . $file_extension; // Tên file độc nhất
        $target_file = $target_dir . $file_name;
        
        // Chỉ cho phép file ảnh
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array(strtolower($file_extension), $allowed)) {
            if (move_uploaded_file($_FILES["topic_image"]["tmp_name"], $target_file)) {
                $image = $file_name;
            }
        } else {
            echo "<script>alert('Chỉ chấp nhận file ảnh (jpg, png, gif, webp)!');</script>";
        }
    }

    // Câu lệnh INSERT
    $stmt = $conn->prepare("INSERT INTO topics (topic_name, description, image) VALUES (?, ?, ?)");
    $stmt->execute([$name, $desc, $image]);
    echo "<script>alert('Thêm chủ đề thành công!'); window.location.href='manage_topics.php';</script>";
}

// Xử lý Xóa Chủ đề (Code cũ của bạn)
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    try {
        $conn->prepare("DELETE FROM vocabularies WHERE topic_id = ?")->execute([$id]);
        $stmt = $conn->prepare("SELECT image FROM topics WHERE topic_id = ?");
        $stmt->execute([$id]);
        $img = $stmt->fetchColumn();
        if ($img && $img != 'default.png' && file_exists("../../uploads/$img")) { unlink("../../uploads/$img"); }
        $conn->prepare("DELETE FROM topics WHERE topic_id = ?")->execute([$id]);
        header("Location: manage_topics.php"); exit;
    } catch (PDOException $e) {
        echo "<script>alert('Lỗi: " . $e->getMessage() . "'); window.location.href='manage_topics.php';</script>";
    }
}
//SEARCH & PHAN TRANG
// 1. XỬ LÝ TÌM KIẾM
$search = trim($_GET['search'] ?? '');
$search_sql = "";
$params = []; // Mảng chứa tham số

if ($search !== '') {
    $search_sql = " WHERE topic_name LIKE ? OR description LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// 2. PHÂN TRANG & LẤY DỮ LIỆU
$sql_count = "SELECT COUNT(*) FROM topics $search_sql";
$sql_data  = "SELECT * FROM topics $search_sql ORDER BY topic_id DESC";

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Số chủ đề mỗi trang

// 3. Gọi hàm (Truyền biến $params vào thay vì mảng rỗng [])
$paging_result = getPagingData($conn, $sql_count, $sql_data, $params, $limit, $page);

// 4. Lấy dữ liệu
$topics = $paging_result['data'];
$total_pages = $paging_result['total_pages'];
$current_page = $paging_result['current_page'];
$total_records = $paging_result['total_records'];


include '../../includes/header.php';
?>
<style>
    .btn-danger:hover {
    background: #d4402f;
    }
</style>
<div >
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="color: var(--primary-color);"><i class="fas fa-tags"></i> Quản lý Chủ đề (Topics)</h2>
        <a href="../../admin/dashboard.php" class="btn" style="background: #777;">&larr; Quay lại Dashboard</a>
    </div>

    <span class="badge" style="background: #eee; color: #333; padding: 10px;">Tổng: <b><?= $total_records ?></b> Chủ đề</span>
    
    <form method="GET" style="margin-top: 30px;margin-bottom:30px ;display: flex; gap: 10px;">
            <input required type="text" name="search" placeholder="Tìm kiếm..." value="<?= htmlspecialchars($search) ?>" style="width: 100%;margin: 0;padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
            <button type="submit" class="btn" style="background: var(--primary-color);width:200px;padding:0"><i class="fas fa-search"></i> Tìm kiếm</button>
            <?php if($search !== ''): ?>
                <a href="manage_topics.php" class="btn" style="background: #95a5a6;align-content: center;width:100px;"><i class="fas fa-times"></i> Hủy</a>
            <?php endif; ?>
    </form>

    <div style="background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-top:20px">
        <h4 style="margin-top: 0;">Thêm Chủ đề mới</h4>
        
        <form method="POST" enctype="multipart/form-data" style="display: flex; gap: 20px; align-items: flex-start;">
            
            <div style="flex: 2; display: flex; flex-direction: column; gap: 10px;">
                <input type="text" name="topic_name" placeholder="Tên chủ đề (VD: Animals)" required style="width: 100%; padding: 10px;">
                <input type="text" name="description" placeholder="Mô tả ngắn" style="width: 100%; padding: 10px;">
                
                <label class="file-label" style="font-size: 14px; display: block; margin-bottom: 5px;">Ảnh bìa:
                    <input style="display: none;" type="file" name="topic_image" onchange="previewImage(this)" accept="image/*" style="width: 100%;">
                    <span class="file-btn">📂 Chọn file</span>
                </label>

            </div>

            <div style="flex: 1; text-align: center;">
                <div style="width: 150px; height: 150px; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; overflow: hidden; border-radius: 8px; background: #f9f9f9; margin: 0 auto;">
                    <img id="img-preview" src="#" alt="Xem trước" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                    <span id="text-preview" style="color: #999; font-size: 12px;">Xem trước ảnh</span>
                </div>
                <button type="submit" name="add_topic" class="btn" style="background:var(--success-color); margin-top: 10px; width: 150px;">Lưu Chủ đề</button>
            </div>

        </form>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 5%;">ID</th>
                <th style="width: 10%;">Hình ảnh</th>
                <th style="width: 20%;">Tên Chủ đề</th>
                <th style="width: 40%;">Mô tả</th> 
                <th style="text-align: center; white-space: nowrap; width: 25%;">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($topics as $t): ?>
            <tr>
                <td>#<?= $t['topic_id'] ?></td>
                <td>
                    <?php if (!empty($t['image'])): ?>
                        <img src="../../uploads/<?= $t['image'] ?>" width="60" height="60" style="object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                    <?php else: ?>
                        <span style="color: #999;">No image</span>
                    <?php endif; ?>
                </td>
                <td><b><?= htmlspecialchars($t['topic_name']) ?></b></td>
                
                <td>
                    <div style="display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; line-height: 1.5;" 
                        title="<?= htmlspecialchars($t['description']) ?>">
                        <?= htmlspecialchars($t['description']) ?>
                    </div>
                </td>
                
                <td style="text-align: center; white-space: nowrap;">
                    <a href="manage_vocab.php?topic_id=<?= $t['topic_id'] ?>" class="btn">Từ vựng</a>
                    <a href="edit_topic.php?id=<?= $t['topic_id'] ?>" class="btn btn-warning">Sửa</a>
                    <a href="manage_topics.php?delete_id=<?= $t['topic_id'] ?>" 
                    class="btn btn-danger" 
                    onclick="return confirm('Bạn có chắc muốn xóa chủ đề này?')">Xóa</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php renderPagingUI($total_pages, $current_page); ?>
    
</div>

<script>
    function previewImage(input) {
        var preview = document.getElementById('img-preview');
        var text = document.getElementById('text-preview');
        
        // Nếu người dùng có chọn file
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            
            // Khi đọc file xong thì gán link vào src của thẻ img
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.style.display = 'block'; // Hiện ảnh
                text.style.display = 'none';     // Ẩn chữ "Xem trước ảnh"
            }
            
            reader.readAsDataURL(input.files[0]); // Bắt đầu đọc file
        } else {
            // Nếu bỏ chọn thì ẩn ảnh đi
            preview.style.display = 'none';
            text.style.display = 'block';
        }
    }
</script>

<?php include '../../includes/footer.php'; ?>