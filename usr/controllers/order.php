<?php
session_start();
include('./server/connectdb.php');
if (isset($_POST['pay-btn'])) {
    $cost = $_SESSION['total'];
    $state = "on_hold";
    $name = $_POST['co-name'];
    $phone = $_POST['co-phone'];
    $city = $_POST['co-city'];
    $address = $_POST['co-address'];
    $usr_id = $_SESSION['usr_id'] ?? null;
    $email = $_POST['co-email'];
    $dateoforder = date("Y-m-d H:i:s");

    if (!$usr_id) {
        die("⚠ Lỗi: Không tìm thấy ID người dùng.");
    }

    try {

        $conn->beginTransaction();

        $stmt = $conn->prepare("INSERT INTO orders (totalcost, state, usr_id, usr_name, usr_phone, usr_city, usr_address, usr_email, ordate) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);");
        $stmt->bindValue(1, $cost, PDO::PARAM_STR);
        $stmt->bindValue(2, $state, PDO::PARAM_STR);
        $stmt->bindValue(3, $usr_id, PDO::PARAM_INT);
        $stmt->bindValue(4, $name, PDO::PARAM_STR);
        $stmt->bindValue(5, $phone, PDO::PARAM_STR);
        $stmt->bindValue(6, $city, PDO::PARAM_STR);
        $stmt->bindValue(7, $address, PDO::PARAM_STR);
        $stmt->bindValue(8, $email, PDO::PARAM_STR);
        $stmt->bindValue(9, $dateoforder, PDO::PARAM_STR);
        $stmt->execute();
        $orderId = $conn->lastInsertId();
        if (!$orderId) {
            throw new Exception("⚠ Lỗi: Không thể lấy ID đơn hàng.");
        }
        if (!empty($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $key => $product) {
                $selected_size = isset($product['sizes']) ? trim($product['sizes']) : 'N/A';
                $product_id = $product['product_id'];
                $product_image = $product['product_image'];
                $product_name = $product['product_name'];
                $product_price = $product['product_price'];
                $product_quantity = $product['product_sl'];
                $stmt = $conn->prepare("INSERT INTO order_items (product_id, p_image, p_name, p_price, usr_id, ordate, o_id, size, quantity) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bindValue(1, $product_id, PDO::PARAM_INT);
                $stmt->bindValue(2, $product_image, PDO::PARAM_STR);
                $stmt->bindValue(3, $product_name, PDO::PARAM_STR);
                $stmt->bindValue(4, $product_price, PDO::PARAM_STR);
                $stmt->bindValue(5, $usr_id, PDO::PARAM_INT);
                $stmt->bindValue(6, $dateoforder, PDO::PARAM_STR);
                $stmt->bindValue(7, $orderId, PDO::PARAM_INT);
                $stmt->bindValue(8, $selected_size, PDO::PARAM_STR);
                $stmt->bindValue(9, $product_quantity, PDO::PARAM_INT);
                $stmt->execute();

                $stmt = $conn->prepare("UPDATE product_details 
                                        SET stock_quantity = stock_quantity - :quantity 
                                        WHERE product_id = :product_id AND size = :size;");
                $stmt->bindValue(':quantity', $product_quantity, PDO::PARAM_INT);
                $stmt->bindValue(':product_id', $product_id, PDO::PARAM_INT);
                $stmt->bindValue(':size', $selected_size, PDO::PARAM_STR);
                $stmt->execute();
            }
        }

        $conn->commit();

        echo "✅ Đơn hàng đã được tạo thành công!";
        unset($_SESSION['cart']);
    } catch (Exception $e) {
        
        $conn->rollBack();
        die("⚠ Lỗi trong quá trình đặt hàng: " . $e->getMessage());
    }
}


header('location:index.php')
?>
