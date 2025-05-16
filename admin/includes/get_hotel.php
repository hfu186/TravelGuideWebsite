<?php
include 'database_connection.php';

if (isset($_GET['hotel_id'])) {
    $hotel_id = intval($_GET['hotel_id']);
    
    $stmt = $conn->prepare("SELECT * FROM hotels WHERE hotel_id = ?");
    $stmt->bindParam("i", $hotel_id);
    $stmt->execute();

    
    if ($row = $result->fetchAll()) {
        echo json_encode($row);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Không tìm thấy khách sạn']);
    }
    

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Thiếu ID khách sạn']);
}
