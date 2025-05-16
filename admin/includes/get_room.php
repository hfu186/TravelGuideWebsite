<?php
include 'connectdb.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if(isset($_GET['hotel_id'])) {
        $hotel_id = $_GET['hotel_id'];
        
        $query = "SELECT * FROM Rooms WHERE hotel_id = :hotel_id AND is_deleted = 0";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':hotel_id', $hotel_id);
        $stmt->execute();
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rooms);
    } else {
        echo json_encode(["error" => "Thiếu tham số hotel_id"]);
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $query = "INSERT INTO Rooms (hotel_id, room_type, price_per_night, max_guests) VALUES (:hotel_id, :room_type, :price_per_night, :max_guests)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":hotel_id", $data['hotel_id']);
    $stmt->bindParam(":room_type", $data['room_type']);
    $stmt->bindParam(":price_per_night", $data['price_per_night']);
    $stmt->bindParam(":max_guests", $data['max_guests']);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Room added successfully"]);
    } else {
        echo json_encode(["message" => "Failed to add room"]);
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $room_id = $_GET['id'];
    $query = "UPDATE Rooms SET is_deleted = 1 WHERE room_id = :room_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":room_id", $room_id);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Room deleted successfully"]);
    } else {
        echo json_encode(["message" => "Failed to delete room"]);
    }
}
?>
