<?php
session_start();

// DB Connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "animzmax_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle quantity update and item removal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_quantity'])) {
        $product_id = $_POST['item_index'];
        $qty = max(1, intval($_POST['quantity']));
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] = $qty;
        }
    }

    if (isset($_POST['remove_item'])) {
        $product_id = $_POST['item_index'];
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
        }
    }
}

// Calculate grand total
function calculateGrandTotal()
{
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

// Render cart rows (simplified: no category, no image)
function renderCart()
{
    if (empty($_SESSION['cart'])) {
        echo '<tr><td colspan="6" style="text-align:center;">Your cart is empty.</td></tr>';
        return;
    }

    foreach ($_SESSION['cart'] as $product_id => $item) {
        $product_name = htmlspecialchars($item['product_name']);
        $price = number_format($item['price'], 2);
        $quantity = intval($item['quantity']);
        $total = number_format($item['price'] * $item['quantity'], 2);

        echo "<tr>
            <td>{$product_id}</td>
            <td>{$product_name}</td>
            <td>₹{$price}</td>
            <td>
                <form method='POST'>
                    <input type='number' name='quantity' min='1' value='{$quantity}' onchange='this.form.submit()' />
                    <input type='hidden' name='item_index' value='{$product_id}' />
                    <input type='hidden' name='update_quantity' value='1' />
                </form>
            </td>
            <td>₹{$total}</td>
            <td>
                <form method='POST' onsubmit='return confirm(\"Remove this item?\");'>
                    <input type='hidden' name='item_index' value='{$product_id}' />
                    <button type='submit' name='remove_item'>Remove</button>
                </form>
            </td>
        </tr>";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Cart | Animz Max</title>
    <link rel="stylesheet" href="cart.css" />
    <style>
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 10px; border: 1px solid #ccc; text-align: center; vertical-align: middle; }
        .cart-actions { margin-top: 20px; display: flex; justify-content: space-between; }
        .btn-link, button { padding: 10px 20px; text-decoration: none; cursor: pointer; }
    </style>
</head>
<body>

<header>
    <div class="logo">Animz Max</div>
    <nav>
        <a href="index.html">Home</a>
        <a href="search.php" title="Search">🔍</a>
        <a href="product.php">🧥</a>
        <a href="loginpage.php">👤</a>
    </nav>
</header>

<main>
    <h2>Your Shopping Cart</h2>

    <table id="cartTable">
        <thead>
            <tr>
                <th>Product ID</th>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Total</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="cartItems">
            <?php renderCart(); ?>
        </tbody>
    </table>

    <div class="cart-total" style="margin-top: 15px;">
        <?php
        $grandTotal = calculateGrandTotal();
        echo "<strong>Grand Total: ₹" . number_format($grandTotal, 2) . "</strong>";
        ?>
    </div>

    <div class="cart-actions">
        <a href="index.html" class="btn-link">← Continue Shopping</a>
        <?php if (!empty($_SESSION['cart'])): ?>
            <form action="checkout.php" method="POST">
                <button type="submit" id="checkoutBtn">Proceed to Checkout →</button>
            </form>
        <?php else: ?>
            <button disabled style="opacity: 0.5; cursor: not-allowed;">Proceed to Checkout →</button>
        <?php endif; ?>
    </div>
</main>

</body>
</html>
