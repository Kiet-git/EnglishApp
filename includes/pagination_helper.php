<?php
// Hàm tính toán phân trang (Logic)
function getPagingData($conn, $sql_count, $sql_data, $params, $limit, $page) {
    // 1. [BẢO MẬT] Ép kiểu về số nguyên (int) để chống lỗi và tránh SQL Injection
    $limit = (int)$limit;
    $page = (int)$page;

    // 2. Đếm tổng số bản ghi
    $stmt = $conn->prepare($sql_count);
    $stmt->execute($params);
    $total_records = $stmt->fetchColumn();

    // 3. Tính tổng số trang
    $total_pages = ceil($total_records / $limit);

    // 4. Giới hạn trang hiện tại (không < 1 và không > total_pages)
    if ($page < 1) $page = 1;
    if ($page > $total_pages && $total_pages > 0) $page = $total_pages;

    // 5. Tính offset (vị trí bắt đầu lấy dữ liệu)
    $start = ($page - 1) * $limit;
    if ($start < 0) $start = 0; // Đảm bảo an toàn không bị âm

    // 6. Lấy dữ liệu phân trang
    $sql_data .= " LIMIT $start, $limit";
    $stmt = $conn->prepare($sql_data);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'data' => $data,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'total_records' => $total_records
    ];
}

// Hàm hiển thị giao diện phân trang (UI) - Đã nâng cấp
// Hàm hiển thị thanh phân trang (Giao diện) - Đã tối ưu hiển thị dạng 1 2 ... 14
function renderPagingUI($total_pages, $current_page) {
    if ($total_pages <= 1) return; // Nếu chỉ có 1 trang thì ẩn luôn thanh phân trang

    // Lấy tất cả tham số trên URL hiện tại (ví dụ: topic_id, quiz_id, search...)
    $params = $_GET; 

    echo '<div style="margin-top: 20px; text-align: center; display: flex; justify-content: center; align-items: center; flex-wrap: wrap; gap: 5px;">';
    
    // Nút TRƯỚC
    if ($current_page > 1) {
        $params['page'] = $current_page - 1;
        $prev_url = '?' . http_build_query($params);
        echo "<a href='$prev_url' class='btn' style='background:#f1f1f1; color:#333; text-decoration: none; border-radius: 5px; padding: 8px 12px;'>&laquo; Trước</a>";
    }

    // --- LOGIC RÚT GỌN TRANG ---
    $adjacents = 1; // Số trang hiển thị 2 bên của trang hiện tại (VD: hiện tại là 5 thì hiện 4 và 6)
    $last_printed = 0; // Biến theo dõi trang vừa in ra để biết lúc nào cần in dấu ...

    for ($i = 1; $i <= $total_pages; $i++) {
        // Điều kiện để in số trang: 
        // 1. Là trang đầu tiên (1)
        // 2. Là trang cuối cùng ($total_pages)
        // 3. Nằm trong khoảng lân cận của trang hiện tại (current - 1 đến current + 1)
        if ($i == 1 || $i == $total_pages || ($i >= $current_page - $adjacents && $i <= $current_page + $adjacents)) {
            
            // Nếu khoảng cách giữa trang đang xét và trang vừa in lớn hơn 1 -> Bị đứt quãng -> In dấu ...
            if ($last_printed > 0 && $i - $last_printed > 1) {
                echo "<span style='padding: 8px 12px; color: #777;'>...</span>";
            }

            $params['page'] = $i; // Cập nhật số trang
            $query_string = http_build_query($params); // Tạo chuỗi URL chuẩn
            
            // KIỂM TRA TRANG HIỆN TẠI ĐỂ TÔ MÀU
            if ($i == $current_page) {
                // Màu sáng (Active)
                $style = 'background: var(--primary-color, #3498db); color: #fff; border: 1px solid var(--primary-color, #3498db); font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.2);';
            } else {
                // Màu thường
                $style = 'background: #fff; color: #555; border: 1px solid #ccc; transition: 0.2s;';
            }
                
            echo "<a href='?$query_string' class='btn page-item' style='$style padding: 8px 12px; text-decoration: none; border-radius: 5px;'>$i</a>";
            
            $last_printed = $i; // Cập nhật lại trang vừa in
        }
    }

    // Nút SAU
    if ($current_page < $total_pages) {
        $params['page'] = $current_page + 1;
        $next_url = '?' . http_build_query($params);
        echo "<a href='$next_url' class='btn' style='background:#f1f1f1; color:#333; text-decoration: none; border-radius: 5px; padding: 8px 12px;'>Sau &raquo;</a>";
    }

    echo '</div>';
    
    // JS hỗ trợ hover nhẹ nhàng cho nút (Giữ nguyên)
    echo "<style>
        .page-item:hover:not([style*='color: #fff']) {
            background: #e9ecef !important;
        }
    </style>";
}
?>