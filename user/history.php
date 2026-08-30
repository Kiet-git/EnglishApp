<?php
require '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
include '../includes/header.php';

if (!isset($_SESSION['user_id'])) header("Location: ../auth/login.php");

$user_id = $_SESSION['user_id'];

// [SỬA SQL] Join thêm bảng users (u) để lấy thông tin người tạo bài thi (created_by)
$sql = "SELECT r.*, q.title, u.username as creator_name, u.role as creator_role
        FROM test_results r 
        JOIN quizzes q ON r.quiz_id = q.quiz_id 
        LEFT JOIN users u ON q.created_by = u.user_id
        WHERE r.user_id = ? 
        ORDER BY r.test_date DESC";

$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$history = $stmt->fetchAll();
?>

<div >
    <h2><i class="fas fa-history"></i> Lịch sử thi của bạn</h2>
    <a href="../modules/quiz/quiz_list.php" class="btn" style="margin-bottom: 20px;">&larr; Quay lại danh sách bài thi</a>

    <?php if(count($history) == 0): ?>
        <p>Bạn chưa làm bài thi nào.</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
            <thead>
                <tr style="background-color: #f8f9fa; text-align: left;">
                    <th style="padding: 12px; border-bottom: 2px solid #ddd;">Ngày thi</th>
                    <th style="padding: 12px; border-bottom: 2px solid #ddd;">Tên bài thi</th>
                    <th style="padding: 12px; border-bottom: 2px solid #ddd;">Tạo bởi</th>
                    <th style="padding: 12px; border-bottom: 2px solid #ddd;">Điểm số</th>
                    <th style="padding: 12px; border-bottom: 2px solid #ddd;">Đánh giá</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($history as $h): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px; color: #555;"><?= date('d/m/Y H:i', strtotime($h['test_date'])) ?></td>
                    <td style="padding: 12px; font-weight: 500; color: #2c3e50;"><?= htmlspecialchars($h['title']) ?></td>
                    
                    <td style="padding: 12px;">
                        <?php if ($h['creator_role'] == 'admin' || empty($h['creator_name'])): ?>
                            <span style="background: #e8f8f5; color: #e74c3c; padding: 4px 10px; border-radius: 12px; font-size: 0.85rem; font-weight: bold;">
                                <i class="fas fa-server"></i> <strong>Hệ Thống</strong> 
                            </span>
                        <?php else: ?>
                            <span style="background: #f4f6f7; color: #2ecc71; padding: 4px 10px; border-radius: 12px; font-size: 0.85rem;">
                                <i class="fas fa-user"></i> <strong>Của Tôi</strong>
                            </span>
                        <?php endif; ?>
                    </td>

                    <td style="padding: 12px;">
                        <b style="font-size: 1.2rem; color: <?= $h['score'] >= 5 ? '#2ecc71' : '#e74c3c' ?>">
                            <?= $h['score'] ?>
                        </b>
                    </td>
                    <td style="padding: 12px;">
                        <?php 
                            if($h['score'] >= 8) echo "<span style='color: #2980b9; font-weight: bold;'>Giỏi</span>";
                            elseif($h['score'] >= 6.5) echo "<span style='color: #27ae60; font-weight: bold;'>Khá</span>";
                            elseif($h['score'] >= 5) echo "<span style='color: #f39c12; font-weight: bold;'>Trung bình</span>";
                            else echo "<span style='color: #c0392b; font-weight: bold;'>Yếu</span>";
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>