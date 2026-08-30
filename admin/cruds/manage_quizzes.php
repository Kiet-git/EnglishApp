<?php

require '../../config/db.php';

// Kiểm tra helper phân trang
if (file_exists('../../includes/pagination_helper.php')) {
    require '../../includes/pagination_helper.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra quyền Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}


// 1. XỬ LÝ THÊM BÀI THI
if (isset($_POST['add_quiz'])) {

    $title = $_POST['title'];
    $creator_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO quizzes (title, created_by) VALUES (?, ?)");
    $stmt->execute([$title, $creator_id]);

    header("Location: manage_quizzes.php");
    exit;
}


// 2. XỬ LÝ XÓA BÀI THI
if (isset($_GET['del_quiz'])) {

    $id = $_GET['del_quiz'];

    try {

        $conn->prepare("DELETE FROM test_results WHERE quiz_id = ?")->execute([$id]);
        $conn->prepare("DELETE FROM questions WHERE quiz_id = ?")->execute([$id]);
        $conn->prepare("DELETE FROM quizzes WHERE quiz_id = ?")->execute([$id]);

        header("Location: manage_quizzes.php");
        exit;

    } catch (PDOException $e) {

        echo "<script>alert('Lỗi: " . $e->getMessage() . "'); window.location.href='manage_quizzes.php';</script>";
        exit;
    }
}


// SEARCH + PHÂN TRANG
$search = trim($_GET['search'] ?? '');
$search_sql = "";
$params = [];

if ($search !== '') {
    $search_sql = " AND q.title LIKE ?";
    $params[] = "%$search%";
}

$sql_count = "SELECT COUNT(*) FROM quizzes q
              JOIN users u ON q.created_by = u.user_id
              WHERE u.role = 'admin' $search_sql";

$sql_data  = "SELECT q.* FROM quizzes q
              JOIN users u ON q.created_by = u.user_id
              WHERE u.role = 'admin' $search_sql
              ORDER BY q.quiz_id DESC";

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;

if (function_exists('getPagingData')) {

    $paging_result = getPagingData($conn, $sql_count, $sql_data, $params, $limit, $page);

    $quizzes = $paging_result['data'];
    $total_pages = $paging_result['total_pages'];
    $current_page = $paging_result['current_page'];
    $total_records = $paging_result['total_records'];

} else {

    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->execute($params);
    $total_records = $stmt_count->fetchColumn();

    $total_pages = ceil($total_records / $limit);
    $offset = ($page - 1) * $limit;

    $stmt_data = $conn->prepare($sql_data . " LIMIT $limit OFFSET $offset");
    $stmt_data->execute($params);

    $quizzes = $stmt_data->fetchAll();
    $current_page = $page;
}


include '../../includes/header.php';

?>

<style>
    .btn-danger:hover { background: #d4402f; }
</style>

<div >
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">
        <h2 style="color: var(--primary-color); margin: 0;"><i class="fas fa-tasks"></i> Quản lý Đề Thi (Hệ thống)</h2>
        <a href="../../admin/dashboard.php" class="btn" style="background: #777;">&larr; Quay về Dashboard</a>
    </div>
    <span class="badge" style="background: #eee; color: #333; padding: 10px;">Tổng: <b><?= $total_records ?></b> Bài thi</span>

    <form method="GET" style="margin-top: 30px;margin-bottom:30px ;display: flex; gap: 10px;">
            <input required type="text" name="search" placeholder="Tìm kiếm..." value="<?= htmlspecialchars($search) ?>" style="width: 100%;margin: 0;padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
            <button type="submit" class="btn" style="background: var(--primary-color);width:200px;padding:0"><i class="fas fa-search"></i> Tìm kiếm</button>
            <?php if($search !== ''): ?>
                <a href="manage_quizzes.php" class="btn" style="background: #95a5a6;align-content: center;width:100px;"><i class="fas fa-times"></i> Hủy</a>
            <?php endif; ?>
    </form>

    <div style="background: #eef; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px dashed #ccc;margin-top:20px">
        <form method="POST" style="display: flex; gap: 10px;">
            <input type="text" name="title" placeholder="Nhập tên bài thi mới (VD: Bài kiểm tra Tổng hợp số 1)..." required style="flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 6px 0 8px 0;">
            <button type="submit" name="add_quiz" class="btn" style="background:var(--success-color);">
                <i class="fas fa-plus-circle"></i> Tạo bài thi
            </button>
        </form>
    </div>

    <?php if(empty($quizzes)): ?>
        <p style="text-align: center; color: #666; padding: 20px;">Chưa có bài thi nào của hệ thống.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Tên Bài thi</th>
                    <th>Quy mô</th> 
                    <th>Ngày tạo</th>
                    <th style="text-align: center; width: 250px;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quizzes as $q): 
                    // Đếm số câu hỏi
                    $count = $conn->query("SELECT COUNT(*) FROM questions WHERE quiz_id=".$q['quiz_id'])->fetchColumn();
                ?>
                <tr>
                    <td>#<?= $q['quiz_id'] ?></td>
                    <td style="font-weight: bold; color: #2c3e50;"><?= htmlspecialchars($q['title']) ?></td>
                    <td>
                        <span style="background: #e1f5fe; color: #0288d1; padding: 2px 8px; border-radius: 10px; font-size: 0.85rem;">
                            <?= $count ?> câu hỏi
                        </span>
                    </td>
                    <td style="color: #666; font-size: 0.9rem;">
                        <?= isset($q['created_at']) ? date('d/m/Y', strtotime($q['created_at'])) : '---' ?>
                    </td>
                    <td style="text-align: center;">
                        <a href="manage_questions.php?quiz_id=<?= $q['quiz_id'] ?>" class="btn" style="padding: 5px 10px; font-size: 0.85rem;" title="Soạn câu hỏi">
                            <i class="fas fa-edit"></i> Soạn câu
                        </a>
                        <a href="edit_quiz.php?id=<?= $q['quiz_id'] ?>" class="btn btn-warning" style="padding: 5px 10px; font-size: 0.85rem;" title="Sửa tên">
                            <i class="fas fa-pen"></i>
                        </a>
                        <a href="manage_quizzes.php?del_quiz=<?= $q['quiz_id'] ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.85rem;" onclick="return confirm('CẢNH BÁO: Xóa bài này sẽ mất toàn bộ câu hỏi và lịch sử điểm của học viên. Tiếp tục?')" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php 
            if (function_exists('renderPagingUI')) {
                renderPagingUI($total_pages, $current_page);
            }
        ?>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>