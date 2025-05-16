<?php
$servername = "127.0.0.1:3306";
$username = "root";
$password = "";
$dbname = "mktrip";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    echo "Fail to connect!: " . $e->getMessage();
}
?>