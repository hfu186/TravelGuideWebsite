<?php
require '../../server/connectdb.php';

$result = [];
$searchTerm = '';
$errorMessage = '';

try {
    if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
        $searchTerm = trim($_GET['search']);
        $search = "%" . $searchTerm . "%";
        $sql = "
        SELECT 
            type, 
            id, 
            name, 
            destination, 
            price, 
            rating, 
            image_url,
            CASE 
                WHEN type = 'Tour' THEN 1
                WHEN type = 'Ticket' THEN 2

                ELSE 5
            END AS type_priority
        FROM (
            SELECT 'Tour' AS type, t.tour_id AS id, t.tour_name AS name, 
                   d.name AS destination, t.price, t.rating, t.image_url
            FROM Tours t
            JOIN Destinations d ON t.destination_id = d.destination_id
            WHERE d.name LIKE ? OR t.tour_name LIKE ?

            UNION ALL

            SELECT 'Ticket' AS type, tk.ticket_id AS id, tk.ticketname AS name, 
                   tk.location AS destination, tk.price, tk.rating, tk.img_url
            FROM Tickets tk
            WHERE tk.location LIKE ? OR tk.ticketname LIKE ?
        ) AS combined_results
        ORDER BY rating DESC, type_priority
        LIMIT 20";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $search,
            $search,  
            $search,
            $search,   
           
        ]);

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($result)) {
            $errorMessage = "Không tìm thấy kết quả nào cho từ khóa: " . htmlspecialchars($searchTerm);
        }
    }
} catch (PDOException $e) {
    $errorMessage = "Đã xảy ra lỗi trong quá trình tìm kiếm. Vui lòng thử lại sau.";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include '../layout/header.php'; ?>
    <meta charset="UTF-8">
    <title>Kết quả tìm kiếm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: rgb(255, 255, 255);
            font-family: 'Arial', sans-serif;
            animation: fadeIn 1s ease-in;
        }

        .card-custom:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .card-img-top {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .card-body {
            padding: 15px;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
        }

        .card-text {
            font-size: 1rem;
            color: #555;
        }

        .btn-warning {
            background-color: #ff9800;
            border: none;
            color: white;
            padding: 8px 12px;
            font-size: 1rem;
            border-radius: 6px;
            transition: background-color 0.3s ease;
        }

        .btn-warning:hover {
            background-color: #e68900;
        }

        .alert-info {
            text-align: center;
            font-size: 1.1rem;
            font-weight: bold;
            color: #007bff;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <div class="row">
            <?php if (count($result) > 0): ?>
                <?php foreach ($result as $row): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card card-custom h-100 ">
                            <img src="<?php echo !empty($row['image_url']) ? htmlspecialchars($row['image_url']) : 'default-image.jpg'; ?>"
                                class="card-img-top" alt="Image">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($row['name'] ?? 'Không có tên'); ?></h5>
                                <p class="card-text">Điểm đến:
                                    <?php echo htmlspecialchars($row['destination'] ?? 'Không có điểm đến'); ?>
                                </p>
                                <p class="card-text">Giá:
                                    <?php echo isset($row['price']) ? number_format($row['price']) . " VND" : 'Liên hệ'; ?>
                                </p>
                                <a href="
    <?php
            switch ($row['type']) {
                case 'Tour':
                    echo "./detail_tour.php?id=" . $row['id'];
                    break;
                case 'Ticket':
                    echo "./detail_tickets.php?id=" . $row['id'];
                    break;
            }
            ?>" class="btn btn-warning">Xem chi tiết
                                </a>


                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <?php if (isset($_GET['search'])): ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            Không tìm thấy kết quả nào phù hợp với từ khóa "<?php echo htmlspecialchars($searchTerm); ?>".
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    </div>
    <?php include '../layout/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>