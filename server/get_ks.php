<?php

include('connectdb.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $city = isset($_GET['city']) ? $_GET['city'] : null;

    // Khởi tạo câu truy vấn SQL
    $sql = "
        SELECT h.hotel_id, h.hotel_name, h.location, h.rating, h.img_url, 
               MIN(r.price_per_night) AS minprice
        FROM hotels h
        LEFT JOIN rooms r ON h.hotel_id = r.hotel_id
    ";

    if ($city) {
        $sql .= " WHERE h.destination_id = :city";
    }

    // Nhóm kết quả theo ID khách sạn
    $sql .= " GROUP BY h.hotel_id, h.hotel_name, h.location, h.rating, h.img_url";

    $stmt = $conn->prepare($sql);

    // Gán giá trị tham số nếu có thành phố
    if ($city) {
        $stmt->bindParam(':city', $city);
    }

    $stmt->execute();
    $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($hotels, JSON_UNESCAPED_UNICODE);
    exit;
} catch (PDOException $e) {
    echo json_encode(['error' => 'Lỗi truy vấn: ' . $e->getMessage()]);
    exit;
}
?>
