<?php
require '../config/db.php';
include '../includes/header.php';

// Check Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { header("Location: ../auth/login.php"); exit; }

// --- 1. SỐ LIỆU TỔNG QUAN (ĐÃ FIX LỌC ADMIN) ---

// Tổng User (Student)
$total_users = $conn->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();

// Tổng Từ vựng
$total_vocab = $conn->query("SELECT COUNT(*) FROM vocabularies")->fetchColumn();

// [FIX] Tổng lượt làm bài (Chỉ đếm bài do Admin tạo VÀ Học viên làm)
$sql_tests = "SELECT COUNT(*) 
              FROM test_results tr 
              JOIN quizzes q ON tr.quiz_id = q.quiz_id 
              JOIN users u_creator ON q.created_by = u_creator.user_id 
              JOIN users u_taker ON tr.user_id = u_taker.user_id
              WHERE u_creator.role = 'admin' AND u_taker.role = 'student'";
$total_tests = $conn->query($sql_tests)->fetchColumn();

// [FIX] Điểm trung bình (Chỉ tính trên bài do Admin tạo VÀ Học viên làm)
$sql_avg = "SELECT AVG(tr.score) 
            FROM test_results tr 
            JOIN quizzes q ON tr.quiz_id = q.quiz_id 
            JOIN users u_creator ON q.created_by = u_creator.user_id 
            JOIN users u_taker ON tr.user_id = u_taker.user_id
            WHERE u_creator.role = 'admin' AND u_taker.role = 'student'";
$avg_score = round($conn->query($sql_avg)->fetchColumn(), 1);

?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    .dashboard-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: space-between; transition: transform 0.2s; border-left: 5px solid #ccc; }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); }
    .stat-info h3 { font-size: 2.2rem; margin: 0; font-weight: bold; color: #333; }
    .stat-info p { margin: 5px 0 0; color: #777; font-size: 0.95rem; }
    .stat-icon { font-size: 3rem; opacity: 0.2; }
    
    .card-blue { border-color: #3498db; } .card-blue .stat-icon { color: #3498db; }
    .card-green { border-color: #2ecc71; } .card-green .stat-icon { color: #2ecc71; }
    .card-orange { border-color: #f39c12; } .card-orange .stat-icon { color: #f39c12; }
    .card-red { border-color: #e74c3c; } .card-red .stat-icon { color: #e74c3c; }

    .action-panel { background: #eef2f7; padding: 30px; border-radius: 15px; text-align: center; margin-top: 20px; border: 2px dashed #cbd5e0; }
    .btn-big { padding: 15px 30px; font-size: 1.1rem; border-radius: 8px; margin: 0 10px; display: inline-block; text-decoration: none; color: white; transition: 0.3s; }
    .btn-stats { background: #6c5ce7; } .btn-stats:hover { background: #5b4cc4; }
</style>

<div class="dashboard-container">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px;">
        <h2 style="color: var(--primary-color); margin: 0;"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>
        <div style="font-size: 0.9rem; color: #666;">
            Xin chào, Admin!
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card card-blue">
            <div class="stat-info"><h3><?= $total_users ?></h3><p>Tổng Học viên</p></div>
            <div class="stat-icon"><i class="fas fa-users"></i></div>
        </div>
        <div class="stat-card card-green">
            <div class="stat-info"><h3><?= $total_vocab ?></h3><p>Tổng Từ vựng</p></div>
            <div class="stat-icon"><i class="fas fa-book-open"></i></div>
        </div>
        <div class="stat-card card-orange">
            <div class="stat-info"><h3><?= $total_tests ?: 0 ?></h3><p>Lượt làm bài (HT)</p></div>
            <div class="stat-icon"><i class="fas fa-pen-nib"></i></div>
        </div>
        <div class="stat-card card-red">
            <div class="stat-info"><h3><?= $avg_score ?: 0 ?></h3><p>Điểm TB (Hệ thống)</p></div>
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <h4 style="border-bottom: 1px solid #eee; padding-bottom: 10px;"><i class="fas fa-database"></i> Quản lý Nội dung</h4>
            <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 15px;">
                <a href="cruds/manage_users.php" class="btn" style="text-align: left;"><i class="fas fa-user-graduate"></i> Quản lý Học viên</a>
                <a href="cruds/manage_topics.php" class="btn" style="text-align: left;"><i class="fas fa-tags"></i> Quản lý Chủ đề</a>
                <a href="cruds/manage_quizzes.php" class="btn" style="text-align: left;"><i class="fas fa-file-alt"></i> Quản lý Bài thi</a>
                <a href="cruds/manage_readings.php" class="btn" style="text-align: left;"><i class="fas fa-book-open"></i> Quản lý Bài Reading</a>
            </div>
        </div>

        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <h4 style="border-bottom: 1px solid #eee; padding-bottom: 10px;"><i class="fas fa-tools"></i> Công cụ Admin</h4>
            <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 15px;">
                <a href="cruds/manage_transactions.php" class="btn" style="text-align: left; background:#27ae60;"><i class="fa-solid fa-money-bill"></i> Duyệt giao dịch</a>
                <a href="imports/auto_generate_vocab.php" class="btn btn-info" style="text-align: left;"><i class="fas fa-magic"></i> Auto Generate Từ Vựng</a>
                <a href="imports/quick_imports.php" class="btn btn-warning" style="text-align: left;"><i class="fas fa-file-import"></i> Import Từ Vựng & Câu Hỏi</a>
                <a href="../admin/exports/export_stats.php" class="btn" style="background:#27ae60; text-align: left;"><i class="fas fa-file-export"></i> Xuất Báo Cáo Excel</a>
                
            </div>
        </div>

        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; border: 2px dashed #6c5ce7; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;">
            <i class="fas fa-chart-pie" style="font-size: 3rem; color: #6c5ce7; margin-bottom: 15px;"></i>
            <p style="color: #666; margin-bottom: 15px;">Xem biểu đồ phân tích chi tiết về điểm số và xu hướng.</p>
            <a href="../admin/exports/statistics.php" class="btn-big btn-stats">Xem Biểu Đồ & Thống Kê</a>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>