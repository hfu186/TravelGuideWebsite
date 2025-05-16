<?php
require('../../server/connectdb.php');
function displayAvailableSizes($conn, $product_id){
    try {
        $stmt = $conn->prepare("CALL GetAvailableSizes(:product_id)");
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $sizes_available = $stmt->fetchAll(PDO::FETCH_COLUMN);


        if (!empty($sizes_available)) {
            echo '<select  name="sizes" id="sizes">';
            foreach ($sizes_available as $size) {
                echo "<option class='size-option' value=\"$size\">$size</option>";
            }
            echo '</select>';
        } else {
            echo 'This product is out of stock';
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

if (isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    $stmt = $conn->prepare("SELECT * FROM PRODUCTS WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    header('location: index.php');
    exit();
}

?>
<?php include('header.php') ?>
<section class="container my-5 pt-5">
    <div class="row mt-5">
        <?php if (!empty($product)): ?>
            <?php foreach ($product as $row): ?>

                <div class="col-lg-5 col-md-6 col-sm-12">
                    <img class="img-fluid w-100 pb-1" src=<?php echo $row['product_image']; ?> id="mainimg">
                    <div class="small-img-group">
                        <div class="small-img-col">
                            <img class="small-img" id="small-img" src=<?php echo $row['product_image']; ?> width="100%">
                        </div>
                        <div class="small-img-col">
                            <img class="small-img" id="small-img" src=<?php echo $row['product_image2']; ?> width="100%">
                        </div>
                        <div class="small-img-col">
                            <img class="small-img" id="small-img" src=<?php echo $row['product_image3']; ?> width="100%">
                        </div>
                        <div class="small-img-col">
                            <img class="small-img" id="small-img" src=<?php echo $row['product_image4']; ?> width="100%">
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="detailpd col-lg-6 col-md-12 col-12">
            <h3 class="py-4"><?php echo $row['product_name'] ?></h3>
            <div class="container-group">
                <i class="fas fa-check"></i>
                <p>Authentic 100%</p>
            </div>
            <hr class="hrd">
            <h4>$ <?php echo $row['product_price'] ?></h4>
            <hr class="hrd">
            <form method="POST" action="cart.php" name="size">
                <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                <input type="hidden" name="product_image" value="<?php echo $row['product_image']; ?>">
                <input type="hidden" name="product_name" value="<?php echo $row['product_name']; ?>">
                <input type="hidden" name="product_price" value="<?php echo $row['product_price']; ?>">

                <div class="size-options">
                    <?php
                    if (isset($_GET['product_id'])) {
                        $product_id = intval($_GET['product_id']);
                        displayAvailableSizes($conn, $product_id);
                    } else {
                        echo 'Invalid product ID.';
                    }
                    ?>
                </div>

                <div class="container-group">
                    <form name="size" action="order.php" method="POST">
                        <input type="number" name="product_sl" value="1">
                        <button class="buy-btn" type="submit" name="add">Add To Cart</button>
                        <button class="buy-btn2">Buy Now</button>
                    </form>
                </div>
                <script>
                    const sizeOptions = document.querySelectorAll('.size-option');
                    sizeOptions.forEach(option => {
                        option.addEventListener('click', (event) => {
                            sizeOptions.forEach(opt => opt.classList.remove('selected'));
                            option.classList.add('selected');
                        });
                    });
                </script>
            </form>

        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>

<?php include('footer.php') ?>

</html>