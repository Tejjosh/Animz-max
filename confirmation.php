<?php
session_start();
$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Connect to the database
$conn = new mysqli("localhost", "root", "", "animzmax_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$order = null;
$orderItems = [];

if ($orderId > 0) {
    // Fetch order by order_id
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $order = $result->fetch_assoc();

        // Fetch related order items
        $itemsStmt = $conn->prepare("SELECT product_name, quantity, price FROM order_items WHERE order_id = ?");
        $itemsStmt->bind_param("i", $orderId);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();
        if ($itemsResult && $itemsResult->num_rows > 0) {
            $orderItems = $itemsResult->fetch_all(MYSQLI_ASSOC);
        }
        $itemsStmt->close();
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation | Animz Max</title>
    <link rel="stylesheet" href="checkout.css">
    <style>
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #00573f;
            color: white;
            padding: 15px 30px;
        }
        header .logo {
            font-size: 24px;
            font-weight: bold;
        }
        header nav a {
            color: white;
            margin-left: 20px;
            text-decoration: none;
            font-size: 18px;
        }
        .confirmation-container {
            max-width: 700px;
            margin: 50px auto;
            background: #f8f8f8;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #2c3e50;
        }
        .details, .items {
            margin-top: 20px;
        }
        .details p {
            line-height: 1.6;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table th, table td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: left;
        }
        .back-home {
            display: block;
            margin-top: 30px;
            text-align: center;
            text-decoration: none;
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>
<body>

<header>
    <div class="logo">Animz Max</div>
    <nav>
        <a href="index.html">Home</a>
        <a href="product.php">🧥</a>
        <a href="cart.php">🛒</a>
        <a href="loginpage.php">👤</a>
    </nav>
</header>

<div class="confirmation-container">
    <h1>Order Confirmation</h1>

    <?php if ($order): ?>
        <p>Thank you, <strong><?php echo htmlspecialchars($order['full_name']); ?></strong>!</p>
        <p>Your order has been placed successfully.</p>

        <div class="details">
            <p><strong>Order ID:</strong> <?php echo $order['order_id']; ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
            <p><strong>Shipping Address:</strong><br>
                <?php echo nl2br(htmlspecialchars($order['address'])); ?>,<br>
                <?php echo htmlspecialchars($order['city']); ?> <?php echo htmlspecialchars($order['zip']); ?>
            </p>
            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
            <p><strong>Order Total:</strong> ₹<?php echo number_format($order['order_total'], 2); ?></p>
            <p><strong>Order Date:</strong> <?php echo htmlspecialchars($order['created_at']); ?></p>
        </div>

        <?php if (!empty($orderItems)): ?>
            <div class="items">
                <h3>Items Ordered:</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderItems as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td><?php echo intval($item['quantity']); ?></td>
                                <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                <td>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <p style="color:red;">Sorry, we couldn’t find your order.</p>
    <?php endif; ?>

    <a class="back-home" href="index.html">← Return to Home</a>
</div>

</body>
</html>
