<?php
require '../../config/db.php';

// Chỉ start session nếu nó chưa được start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { 
    header("Location: ../../auth/login.php"); 
    exit; 
}

// --- XỬ LÝ KHI BẤM NÚT XUẤT ---
if (isset($_POST['export_type'])) {
    $type = $_POST['export_type'];
    
    // 1. ĐẶT TÊN FILE TRƯỚC TIÊN (Quan trọng: Không được để dưới cùng)
    $filename = "Export_Data_" . date('Y-m-d') . ".csv";
    if ($type == 'vocab_by_topic') $filename = "TK_TuVung_Theo_ChuDe_" . date('Y-m-d') . ".csv";
    elseif ($type == 'most_studied_topics') $filename = "TK_ChuDe_HocNhieuNhat_" . date('Y-m-d') . ".csv";
    elseif ($type == 'attempts_by_quiz') $filename = "TK_LuotLam_Theo_DeThi_" . date('Y-m-d') . ".csv";
    elseif ($type == 'score_distribution') $filename = "PhanBo_DiemSo_" . date('Y-m-d') . ".csv";
    elseif ($type == 'most_viewed_readings') $filename = "TK_BaiDoc_TruyCapNhieuNhat_" . date('Y-m-d') . ".csv";
    elseif ($type == 'credit_packages') $filename = "TK_DoanhThu_GoiNap_" . date('Y-m-d') . ".csv";

    // 2. KHAI BÁO HEADER ĐỂ TRÌNH DUYỆT TẢI FILE
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // 3. MỞ LUỒNG GHI
    $output = fopen('php://output', 'w');
    // Thêm BOM để Excel đọc được tiếng Việt
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    switch ($type) {
        // 1. THỐNG KÊ TỪ VỰNG THEO CHỦ ĐỀ
        case 'vocab_by_topic':
            fputcsv($output, ['Tên Chủ Đề', 'Mô Tả', 'Số Lượng Từ Vựng']);
            $sql = "SELECT t.topic_name, t.description, COUNT(v.vocab_id) as total 
                    FROM topics t 
                    LEFT JOIN vocabularies v ON t.topic_id = v.topic_id 
                    GROUP BY t.topic_id";
            $stmt = $conn->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [$row['topic_name'], $row['description'], $row['total']]);
            }
            break;
        
        // 2. THỐNG KÊ CHỦ ĐỀ ĐƯỢC HỌC NHIỀU NHẤT
        case 'most_studied_topics':
            fputcsv($output, ['Tên Chủ Đề', 'Mô Tả', 'Lượt Học (Views)']);
            try {
                // Sắp xếp giảm dần theo lượt truy cập
                $sql = "SELECT topic_name, description, views 
                        FROM topics 
                        ORDER BY views DESC 
                        LIMIT 100";
                $stmt = $conn->query($sql);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    fputcsv($output, [$row['topic_name'], $row['description'], $row['views']]);
                }
            } catch (Exception $e) {
                // Bắt lỗi nếu bảng topics chưa có cột views
                fputcsv($output, ['Lỗi', 'Bảng topics chưa có cột views', 'Vui lòng vào phpMyAdmin chạy lệnh: ALTER TABLE topics ADD views INT DEFAULT 0;']);
            }
            break;

        // 3. THỐNG KÊ LƯỢT LÀM BÀI THEO ĐỀ THI (CHỈ BÀI CỦA ADMIN)
        case 'attempts_by_quiz':
            fputcsv($output, ['Tên Bài Thi', 'Tổng Số Lượt Làm', 'Điểm Trung Bình']); 
            $sql = "SELECT q.title, COUNT(r.result_id) as total_attempts, AVG(r.score) as avg_score
                    FROM quizzes q 
                    LEFT JOIN test_results r ON q.quiz_id = r.quiz_id 
                    JOIN users u_creator ON q.created_by = u_creator.user_id
                    WHERE u_creator.role = 'admin'
                    GROUP BY q.quiz_id";
            $stmt = $conn->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $avg = $row['avg_score'] ? round($row['avg_score'], 2) : 0;
                fputcsv($output, [$row['title'], $row['total_attempts'], $avg]);
            }
            break;

        // 4. PHÂN BỐ ĐIỂM SỐ (CHỈ CỦA HỌC VIÊN LÀM BÀI HỆ THỐNG)
        case 'score_distribution':
            fputcsv($output, ['Xếp Loại', 'Khoảng Điểm', 'Số Lượng Học Viên']);
            $sql = "SELECT r.score 
                    FROM test_results r
                    JOIN users u ON r.user_id = u.user_id
                    JOIN quizzes q ON r.quiz_id = q.quiz_id
                    JOIN users u_creator ON q.created_by = u_creator.user_id
                    WHERE u.role = 'student' AND u_creator.role = 'admin'";
            $scores = $conn->query($sql)->fetchAll(PDO::FETCH_COLUMN);
            
            $stats = ['Giỏi' => 0, 'Khá' => 0, 'Trung Bình' => 0, 'Yếu' => 0];
            foreach ($scores as $s) {
                if ($s >= 9) $stats['Giỏi']++;
                elseif ($s >= 7) $stats['Khá']++;
                elseif ($s >= 5) $stats['Trung Bình']++;
                else $stats['Yếu']++;
            }

            fputcsv($output, ['Giỏi', '9.0 - 10.0', $stats['Giỏi']]);
            fputcsv($output, ['Khá', '7.0 - 8.9', $stats['Khá']]);
            fputcsv($output, ['Trung Bình', '5.0 - 6.9', $stats['Trung Bình']]);
            fputcsv($output, ['Yếu', '< 5.0', $stats['Yếu']]);
            break;


        // 5. THỐNG KÊ BÀI ĐỌC ĐƯỢC TRUY CẬP NHIỀU NHẤT (CHỈ BÀI HỆ THỐNG)
        case 'most_viewed_readings':
            fputcsv($output, ['Tiêu Đề Bài Đọc', 'Cấp Độ', 'Lượt Truy Cập (Views)']);
            try {
                // Dùng LEFT JOIN và WHERE để loại bỏ bài đọc của học viên (student)
                $sql = "SELECT r.title, r.level, r.views 
                        FROM readings r 
                        LEFT JOIN users u ON r.user_id = u.user_id 
                        WHERE r.user_id IS NULL OR u.role = 'admin'
                        ORDER BY r.views DESC 
                        LIMIT 100";
                $stmt = $conn->query($sql);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    fputcsv($output, [$row['title'], $row['level'], $row['views']]);
                }
            } catch (Exception $e) {
                fputcsv($output, ['Lỗi', 'Bảng readings chưa có cột views', 'Vui lòng thêm cột views vào database']);
            }
            break;

        // 6. THỐNG KÊ CÁC GÓI NẠP CREDITS PHỔ BIẾN VÀ SỐ LƯỢNG USER ĐÃ NẠP
        case 'credit_packages':
            fputcsv($output, ['Số Tiền Nạp (VNĐ)', 'Số Credits Nhận', 'Tổng Lượt Nạp', 'Số User Đã Nạp', 'Tổng Doanh Thu (VNĐ)']);
            // Bảng `transactions` thường có các cột: amount, credits, status, user_id
            $sql = "SELECT amount, credits, 
                           COUNT(*) as total_tx, 
                           COUNT(DISTINCT user_id) as unique_users, 
                           SUM(amount) as revenue
                    FROM transactions
                    WHERE status = 'success' OR status = 'completed' OR status = 'PAID' OR status = 'paid'
                    GROUP BY amount, credits
                    ORDER BY total_tx DESC";
            try {
                $stmt = $conn->query($sql);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    fputcsv($output, [
                        number_format($row['amount']), 
                        $row['credits'], 
                        $row['total_tx'], 
                        $row['unique_users'], 
                        number_format($row['revenue'])
                    ]);
                }
            } catch (Exception $e) {
                fputcsv($output, ['Lỗi cấu trúc bảng transactions', $e->getMessage()]);
            }
            break;
    }

    fclose($output);
    exit; 
}

include '../../includes/header.php';
?>

<div class="container" style="max-width: 1000px; margin-top: 40px; margin-bottom: 60px;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="color: #27ae60; margin-bottom: 5px;"><i class="fas fa-file-excel"></i> Xuất Báo Cáo Tổng Hợp</h2>
            <p style="color: #7f8c8d;">Chọn loại dữ liệu bạn muốn xuất ra file Excel (.csv)</p>
        </div>
        <a href="../../admin/dashboard.php" class="btn" style="background: #95a5a6;"><i class="fas fa-arrow-left"></i> Quay lại Dashboard</a>
    </div>
    
    <hr style="border: 1px solid #ecf0f1; margin: 20px 0;">

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        
        <div class="auth-box" style="text-align: left; border-top: 4px solid #3498db; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <h3 style="margin-top:0; color:#3498db;"><i class="fas fa-book-open"></i> 1. Thống kê Từ vựng</h3>
            <p style="color:#666; font-size:0.9rem; min-height: 45px;">Xuất danh sách các chủ đề và số lượng từ vựng tương ứng.</p>
            <form method="POST">
                <input type="hidden" name="export_type" value="vocab_by_topic">
                <button class="btn" style="width:100%; background:#3498db;"><i class="fas fa-download"></i> Tải về CSV</button>
            </form>
        </div>

        <div class="auth-box" style="text-align: left; border-top: 4px solid #2ecc71; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <h3 style="margin-top:0; color:#27ae60;"><i class="fas fa-layer-group"></i> 2. Chủ đề Hot nhất</h3>
            <p style="color:#666; font-size:0.9rem; min-height: 45px;">Thống kê các chủ đề từ vựng được học viên truy cập và học nhiều nhất.</p>
            <form method="POST">
                <input type="hidden" name="export_type" value="most_studied_topics">
                <button class="btn" style="width:100%; background:#27ae60; color: white;"><i class="fas fa-download"></i> Tải về CSV</button>
            </form>
        </div>

        <div class="auth-box" style="text-align: left; border-top: 4px solid #e67e22; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <h3 style="margin-top:0; color:#e67e22;"><i class="fas fa-tasks"></i> 3. Thống kê Bài thi</h3>
            <p style="color:#666; font-size:0.9rem; min-height: 45px;">Xem số lượt làm bài và điểm TB của từng đề thi hệ thống.</p>
            <form method="POST">
                <input type="hidden" name="export_type" value="attempts_by_quiz">
                <button class="btn" style="width:100%; background:#e67e22;"><i class="fas fa-download"></i> Tải về CSV</button>
            </form>
        </div>

        <div class="auth-box" style="text-align: left; border-top: 4px solid #9b59b6; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <h3 style="margin-top:0; color:#9b59b6;"><i class="fas fa-chart-pie"></i> 4. Phân Kê Điểm số</h3>
            <p style="color:#666; font-size:0.9rem; min-height: 45px;">Tổng hợp số lượng học viên đạt loại Giỏi, Khá, TB, Yếu.</p>
            <form method="POST">
                <input type="hidden" name="export_type" value="score_distribution">
                <button class="btn" style="width:100%; background:#9b59b6;"><i class="fas fa-download"></i> Tải về CSV</button>
            </form>
        </div>

        <div class="auth-box" style="text-align: left; border-top: 4px solid #1abc9c; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <h3 style="margin-top:0; color:#1abc9c;"><i class="fas fa-book-reader"></i> 5. Bài đọc Phổ biến</h3>
            <p style="color:#666; font-size:0.9rem; min-height: 45px;">Thống kê các bài đọc của hệ thống có lượt truy cập (views) cao nhất.</p>
            <form method="POST">
                <input type="hidden" name="export_type" value="most_viewed_readings">
                <button class="btn" style="width:100%; background:#1abc9c;"><i class="fas fa-download"></i> Tải về CSV</button>
            </form>
        </div>

        <div class="auth-box" style="text-align: left; border-top: 4px solid #f1c40f; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <h3 style="margin-top:0; color:#f39c12;"><i class="fas fa-coins"></i> 6. Doanh Thu Credits</h3>
            <p style="color:#666; font-size:0.9rem; min-height: 45px;">Thống kê các gói nạp Credits phổ biến và tổng doanh thu.</p>
            <form method="POST">
                <input type="hidden" name="export_type" value="credit_packages">
                <button class="btn" style="width:100%; background:#f39c12; color: white;"><i class="fas fa-download"></i> Tải về CSV</button>
            </form>
        </div>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>