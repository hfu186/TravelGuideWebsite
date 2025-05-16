<?php

include('connectdb.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $stmt = $conn->prepare("SELECT * FROM Tour_Guides");
    $stmt->execute();
    $guides = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($guides, JSON_UNESCAPED_UNICODE);
    exit;
} catch (PDOException $e) {
    echo json_encode(['error' => 'Lỗi truy vấn: ' . $e->getMessage()]);
    exit;
}
?>
