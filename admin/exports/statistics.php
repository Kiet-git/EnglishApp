<?php
require '../../config/db.php';
include '../../includes/header.php';

// Check Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { header("Location: ../../auth/login.php"); exit; }

// --- 1. BIỂU ĐỒ TỪ VỰNG THEO CHỦ ĐỀ ---
$sql_chart = "SELECT t.topic_name, COUNT(v.vocab_id) as vocab_count 
              FROM topics t 
              LEFT JOIN vocabularies v ON t.topic_id = v.topic_id 
              GROUP BY t.topic_id";
$chart_data = $conn->query($sql_chart)->fetchAll(PDO::FETCH_ASSOC);
$labels = array_column($chart_data, 'topic_name');
$data_counts = array_column($chart_data, 'vocab_count');

// --- 2. BIỂU ĐỒ USER MỚI (7 ngày qua) ---
$dates = [];
$user_counts = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) = ? AND role='student'");
    $stmt->execute([$date]);
    $dates[] = date('d/m', strtotime($date));
    $user_counts[] = $stmt->fetchColumn();
}

// --- 3. BIỂU ĐỒ ĐIỂM SỐ (Bài do Admin tạo VÀ Học viên làm) ---
$sql_score = "SELECT 
                SUM(CASE WHEN tr.score >= 8 THEN 1 ELSE 0 END) as gio,
                SUM(CASE WHEN tr.score >= 6 AND tr.score < 8 THEN 1 ELSE 0 END) as kha,
                SUM(CASE WHEN tr.score >= 5 AND tr.score < 6 THEN 1 ELSE 0 END) as tb,
                SUM(CASE WHEN tr.score < 5 THEN 1 ELSE 0 END) as yeu
              FROM test_results tr 
              JOIN quizzes q ON tr.quiz_id = q.quiz_id 
              JOIN users u_creator ON q.created_by = u_creator.user_id 
              JOIN users u_taker ON tr.user_id = u_taker.user_id
              WHERE u_creator.role = 'admin' AND u_taker.role = 'student'";
$score_dist = $conn->query($sql_score)->fetch(PDO::FETCH_ASSOC);

// --- 4. BIỂU ĐỒ BÀI THI PHỔ BIẾN NHẤT (Chỉ bài hệ thống) ---
$sql_quiz = "SELECT q.title, COUNT(tr.result_id) as luot_thi 
             FROM quizzes q 
             JOIN test_results tr ON q.quiz_id = tr.quiz_id 
             JOIN users u_taker ON tr.user_id = u_taker.user_id
             LEFT JOIN users u_creator ON q.created_by = u_creator.user_id 
             WHERE u_taker.role = 'student' AND (q.created_by IS NULL OR u_creator.role = 'admin')
             GROUP BY q.quiz_id 
             ORDER BY luot_thi DESC LIMIT 5";
$top_quizzes = $conn->query($sql_quiz)->fetchAll(PDO::FETCH_ASSOC);
$quiz_labels = array_column($top_quizzes, 'title');
$quiz_data = array_column($top_quizzes, 'luot_thi');

// --- 5. BIỂU ĐỒ BÀI ĐỌC HOT NHẤT (Chỉ bài hệ thống) ---
// Note: Cột views đã được cấu hình chỉ cộng khi user không phải admin ở các bước trước
$sql_reading = "SELECT r.title, r.views 
                FROM readings r 
                LEFT JOIN users u ON r.user_id = u.user_id 
                WHERE r.user_id IS NULL OR u.role = 'admin'
                ORDER BY r.views DESC LIMIT 5";
$top_readings = $conn->query($sql_reading)->fetchAll(PDO::FETCH_ASSOC);
$reading_labels = array_column($top_readings, 'title');
$reading_data = array_column($top_readings, 'views');

// --- 6. BIỂU ĐỒ CHỦ ĐỀ HOT NHẤT ---
$sql_topic_hot = "SELECT topic_name, views FROM topics ORDER BY views DESC LIMIT 5";
$top_topics = $conn->query($sql_topic_hot)->fetchAll(PDO::FETCH_ASSOC);
$topic_hot_labels = array_column($top_topics, 'topic_name');
$topic_hot_data = array_column($top_topics, 'views');

// --- 7. BIỂU ĐỒ DOANH THU (7 ngày qua) ---
$rev_dates = [];
$rev_amounts = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $conn->prepare("SELECT SUM(amount) FROM transactions WHERE DATE(created_at) = ? AND status IN ('success', 'completed', 'paid', 'PAID')");
    $stmt->execute([$date]);
    $rev_dates[] = date('d/m', strtotime($date));
    $val = $stmt->fetchColumn();
    $rev_amounts[] = $val ? (int)$val : 0;
}

?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .chart-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
    .chart-box { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-bottom: 25px; }
    .chart-header { text-align: center; margin-bottom: 15px; color: #333; font-weight: bold; }
    .btn-back { display: inline-block; margin-bottom: 20px; background: #555; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
    .row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media(max-width: 768px) { .row { grid-template-columns: 1fr; } }
</style>

<div class="chart-container">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2 style="color: #27ae60;"><i class="fas fa-chart-line"></i> Bảng điều khiển Thống kê</h2>
        <a href="../../admin/dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Quay lại Dashboard</a>
    </div>

    <div class="row">
        <div class="chart-box">
            <h4 class="chart-header">Phân bố Từ vựng theo Chủ đề</h4>
            <div style="height: 300px;"><canvas id="vocabChart"></canvas></div>
        </div>
        <div class="chart-box">
            <h4 class="chart-header">Học viên mới (7 ngày qua)</h4>
            <div style="height: 300px;"><canvas id="userChart"></canvas></div>
        </div>
    </div>

    <div class="row">
        <div class="chart-box">
            <h4 class="chart-header">Chất lượng điểm thi (Bài hệ thống)</h4>
            <div style="height: 300px;"><canvas id="scoreChart"></canvas></div>
        </div>
        <div class="chart-box">
            <h4 class="chart-header">Top 5 Bài thi hệ thống phổ biến</h4>
            <div style="height: 300px;"><canvas id="quizChart"></canvas></div>
        </div>
    </div>

    <div class="row">
        <div class="chart-box">
            <h4 class="chart-header">Top 5 Bài đọc hệ thống Hot nhất (Lượt xem)</h4>
            <div style="height: 300px;"><canvas id="readingChart"></canvas></div>
        </div>
        <div class="chart-box">
            <h4 class="chart-header">Top 5 Chủ đề học Hot nhất (Lượt xem)</h4>
            <div style="height: 300px;"><canvas id="topicHotChart"></canvas></div>
        </div>
    </div>

    <div class="row" style="grid-template-columns: 1fr;">
        <div class="chart-box">
            <h4 class="chart-header">Doanh thu nạp Credits (7 ngày qua)</h4>
            <div style="height: 350px;"><canvas id="revenueChart"></canvas></div>
        </div>
    </div>
</div>

<script>
    Chart.defaults.font.family = "'Segoe UI', sans-serif";
    Chart.defaults.maintainAspectRatio = false;

    // 1. Vocab Chart
    new Chart(document.getElementById('vocabChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{ label: 'Số từ', data: <?= json_encode($data_counts) ?>, backgroundColor: '#3498db' }]
        }
    });

    // 2. User Chart
    new Chart(document.getElementById('userChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($dates) ?>,
            datasets: [{ label: 'User mới', data: <?= json_encode($user_counts) ?>, borderColor: '#2ecc71', backgroundColor: 'rgba(46, 204, 113, 0.1)', fill: true, tension: 0.3 }]
        }
    });

    // 3. Score Chart
    new Chart(document.getElementById('scoreChart'), {
        type: 'doughnut',
        data: {
            labels: ['Giỏi (>=8)', 'Khá', 'TB', 'Yếu (<5)'],
            datasets: [{
                data: [<?= (int)$score_dist['gio'] ?>, <?= (int)$score_dist['kha'] ?>, <?= (int)$score_dist['tb'] ?>, <?= (int)$score_dist['yeu'] ?>],
                backgroundColor: ['#2ecc71', '#3498db', '#f1c40f', '#e74c3c']
            }]
        }
    });

    // 4. Quiz Chart
    new Chart(document.getElementById('quizChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($quiz_labels) ?>,
            datasets: [{ label: 'Lượt làm', data: <?= json_encode($quiz_data) ?>, backgroundColor: ['#ff6384', '#36a2eb', '#ffce56', '#4bc0c0', '#9966ff'] }]
        },
        options: { indexAxis: 'y' }
    });

    // 5. Reading Hot Chart
    new Chart(document.getElementById('readingChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($reading_labels) ?>,
            datasets: [{ label: 'Lượt xem', data: <?= json_encode($reading_data) ?>, backgroundColor: '#1abc9c' }]
        },
        options: { indexAxis: 'y' }
    });

    // 6. Topic Hot Chart
    new Chart(document.getElementById('topicHotChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($topic_hot_labels) ?>,
            datasets: [{ label: 'Lượt xem', data: <?= json_encode($topic_hot_data) ?>, backgroundColor: ['#f39c12', '#d35400', '#e67e22', '#f1c40f', '#e74c3c'] }]
        }
    });

    // 7. Revenue Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($rev_dates) ?>,
            datasets: [{ label: 'Doanh thu (VNĐ)', data: <?= json_encode($rev_amounts) ?>, borderColor: '#e74c3c', backgroundColor: 'rgba(231, 76, 60, 0.1)', fill: true, tension: 0.3 }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) { return value.toLocaleString('vi-VN') + ' đ'; }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) { return context.raw.toLocaleString('vi-VN') + ' đ'; }
                    }
                }
            }
        }
    });
</script>

<?php include '../../includes/footer.php'; ?>