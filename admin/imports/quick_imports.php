<?php
require '../../config/db.php';
// Lưu ý: Không include header ngay ở đây để tránh lỗi header khi tải file mẫu

// --- XỬ LÝ TẢI FILE MẪU (Sample) ---
if (isset($_GET['download_sample'])) {
    $type = $_GET['download_sample'];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=sample_' . $type . '.csv');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM cho tiếng Việt

    if ($type == 'vocab') {
        fputcsv($output, ['Word', 'Word_type', 'Meaning',  'Pronunciation']);
        fputcsv($output, ['Hello', 'Danh từ (noun)', 'Xin chào', '/həˈləʊ/']);
        fputcsv($output, ['Computer', 'Danh từ (noun)', 'Máy tính', '/kəmˈpjuː.tər/']);
    } elseif ($type == 'quiz') {
        fputcsv($output, ['Question', 'Option A', 'Option B', 'Option C', 'Option D', 'Correct Answer']);
        fputcsv($output, ['Con chó là gì?', 'Cat', 'Dog', 'Pig', 'Bird', 'B']);
        fputcsv($output, ['1 + 1 = ?', '1', '2', '3', '4', 'B']);
    }
    fclose($output);
    exit();
}

include '../../includes/header.php';

// Check Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { header("Location: ../../auth/login.php"); exit; }

$alert = null; // Biến lưu thông báo cho SweetAlert

// --- XỬ LÝ UPLOAD TỪ VỰNG ---
if (isset($_POST['import_vocab'])) {
    $topic_id = $_POST['topic_id'];
    
    if (isset($_FILES['file_vocab']) && $_FILES['file_vocab']['size'] > 0) {
        $filename = $_FILES['file_vocab']['tmp_name'];
        $file = fopen($filename, "r");
        fgetcsv($file); // Bỏ header

        $count = 0;
        try {
            $conn->beginTransaction();
            while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
                if(!empty($column[0]) && !empty($column[1])) {
                    $sql = "INSERT INTO vocabularies (topic_id, word, word_type, meaning, pronunciation) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$topic_id, $column[0], $column[1], $column[2], $column[3] ?? '']);
                    $count++;
                }
            }
            $conn->commit();
            $alert = ['type' => 'success', 'title' => 'Thành công!', 'text' => "Đã nhập $count từ vựng."];
        } catch (Exception $e) {
            $conn->rollBack();
            $alert = ['type' => 'error', 'title' => 'Lỗi!', 'text' => $e->getMessage()];
        }
        fclose($file);
    } else {
        $alert = ['type' => 'warning', 'title' => 'Cảnh báo', 'text' => 'Vui lòng chọn file CSV.'];
    }
}

// --- XỬ LÝ UPLOAD CÂU HỎI ---
if (isset($_POST['import_quiz'])) {
    $quiz_id = $_POST['quiz_id'];

    if (isset($_FILES['file_quiz']) && $_FILES['file_quiz']['size'] > 0) {
        $filename = $_FILES['file_quiz']['tmp_name'];
        $file = fopen($filename, "r");
        fgetcsv($file); 

        $count = 0;
        try {
            $conn->beginTransaction();
            while (($col = fgetcsv($file, 10000, ",")) !== FALSE) {
                if(!empty($col[0]) && !empty($col[5])) {
                    $sql = "INSERT INTO questions (quiz_id, content, option_a, option_b, option_c, option_d, correct_answer) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $correct = strtoupper(trim($col[5])); 
                    $stmt->execute([$quiz_id, $col[0], $col[1], $col[2], $col[3], $col[4], $correct]);
                    $count++;
                }
            }
            $conn->commit();
            $alert = ['type' => 'success', 'title' => 'Thành công!', 'text' => "Đã nhập $count câu hỏi."];
        } catch (Exception $e) {
            $conn->rollBack();
            $alert = ['type' => 'error', 'title' => 'Lỗi!', 'text' => $e->getMessage()];
        }
        fclose($file);
    } else {
        $alert = ['type' => 'warning', 'title' => 'Cảnh báo', 'text' => 'Vui lòng chọn file CSV.'];
    }
}

$topics = $conn->query("SELECT * FROM topics")->fetchAll();
// Chỉ lấy danh sách bài thi do Admin tạo
$sql_admin_quizzes = "SELECT q.* FROM quizzes q 
                      JOIN users u ON q.created_by = u.user_id 
                      WHERE u.role = 'admin' 
                      ORDER BY q.quiz_id DESC";
$quizzes = $conn->query($sql_admin_quizzes)->fetchAll();
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    .btn-danger:hover {
        background: #d4402f;
    }
    /* Custom File Input Style */
    .file-upload-box {
        border: 2px dashed #ccc;
        padding: 20px;
        text-align: center;
        border-radius: 8px;
        background: #f9f9f9;
        cursor: pointer;
        transition: 0.3s;
        position: relative;
    }
    .file-upload-box:hover {
        background: #eef;
        border-color: var(--primary-color);
    }
    .file-upload-box input[type="file"] {
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        opacity: 0;
        cursor: pointer;
    }
    .file-info {
        margin-top: 10px;
        font-size: 0.9rem;
        color: #555;
        font-weight: bold;
    }
    .download-link {
        color: var(--primary-color);
        text-decoration: none;
        font-size: 0.85rem;
        display: inline-block;
        margin-bottom: 15px;
    }
    .download-link:hover { text-decoration: underline; }
</style>

<div >
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="margin-bottom: 5px; color:gold" ><i class="fas fa-crown" style="color: gold;"></i> Công cụ IMPORT</h2>
            <p style="color: #666;">Nhập dữ liệu hàng loạt (Bulk Import)</p>
        </div>
        <a href="../../admin/dashboard.php" class="btn" style="background: #777;">&larr; Quay lại Dashboard</a>
    </div>
    
    <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">

    <div style="
    gap: 30px;
    display: flex;
    justify-content: center;">
        
        <div class="auth-box" style="width: 100%; text-align: left; border-top: 4px solid var(--primary-color);">
            <h3 style="color: var(--primary-color); margin-top:0;">1. Nhập Từ Vựng</h3>
            
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <p style="font-size: 0.9rem; margin:0;">File chuẩn: CSV (UTF-8)</p>
                <a href="?download_sample=vocab" class="download-link"><i class="fas fa-download"></i> Tải file mẫu</a>
            </div>
            
            <form method="post" enctype="multipart/form-data" style="margin-top: 15px;">
                <label style="font-weight:bold;">Chọn chủ đề:</label>
                <select name="topic_id" required style="width:100%; padding: 10px; margin-bottom: 15px; border-radius: 5px; border: 1px solid #ccc;">
                    <option value="">-- Chọn chủ đề --</option>
                    <?php foreach($topics as $t): ?>
                        <option value="<?= $t['topic_id'] ?>"><?= $t['topic_name'] ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="file-upload-box">
                    <input type="file" name="file_vocab" accept=".csv" required onchange="updateFileName(this, 'vocab-name')">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: #ccc;"></i>
                    <p style="margin: 5px 0 0;">Kéo file vào đây hoặc Click để chọn</p>
                </div>
                <div id="vocab-name" class="file-info"></div>

                <button type="submit" name="import_vocab" class="btn btn-warning" style="width:100%; margin-top: 15px;">
                    <i class="fas fa-upload"></i> Upload Từ Vựng
                </button>
            </form>
        </div>

        <div class="auth-box" style="width: 100%; text-align: left; border-top: 4px solid #e74c3c;">
            <h3 style="color: #e74c3c; margin-top:0;">2. Nhập Câu Hỏi Thi</h3>

            <div style="display:flex; justify-content:space-between; align-items:center;">
                <p style="font-size: 0.9rem; margin:0;">File chuẩn: CSV (UTF-8)</p>
                <a href="?download_sample=quiz" class="download-link" style="color: #e74c3c;"><i class="fas fa-download"></i> Tải file mẫu</a>
            </div>

            <form method="post" enctype="multipart/form-data" style="margin-top: 15px;">
                <label style="font-weight:bold;">Chọn bài thi:</label>
                <select name="quiz_id" required style="width:100%; padding: 10px; margin-bottom: 15px; border-radius: 5px; border: 1px solid #ccc;">
                    <option value="">-- Chọn bài thi --</option>
                    <?php foreach($quizzes as $q): ?>
                        <option value="<?= $q['quiz_id'] ?>"><?= $q['title'] ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="file-upload-box">
                    <input type="file" name="file_quiz" accept=".csv" required onchange="updateFileName(this, 'quiz-name')">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: #ccc;"></i>
                    <p style="margin: 5px 0 0;">Kéo file vào đây hoặc Click để chọn</p>
                </div>
                <div id="quiz-name" class="file-info"></div>

                <button type="submit" name="import_quiz" class="btn btn-danger" style="width:100%; margin-top: 15px;">
                    <i class="fas fa-upload"></i> Upload Câu Hỏi
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // Hàm hiển thị tên file khi người dùng chọn
    function updateFileName(input, elementId) {
        var fileName = input.files[0] ? input.files[0].name : '';
        var infoDiv = document.getElementById(elementId);
        
        if (fileName) {
            infoDiv.innerHTML = '<i class="fas fa-check-circle" style="color:green"></i> Đã chọn: ' + fileName;
        } else {
            infoDiv.innerHTML = '';
        }
    }

    // Hiển thị thông báo SweetAlert nếu có
    <?php if ($alert): ?>
    Swal.fire({
        icon: '<?= $alert['type'] ?>',
        title: '<?= $alert['title'] ?>',
        text: '<?= $alert['text'] ?>',
        confirmButtonColor: '#3498db',
        timer: 3000 // Tự tắt sau 3 giây
    });
    <?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>