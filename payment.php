<?php
session_start();

// DB connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "animzmax_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";
$cart = (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) ? $_SESSION['cart'] : [];

$cartTotal = 0;
foreach ($cart as $item) {
    $cartTotal += $item['price'] * $item['quantity'];
}

$userData = [];
$userId = null;
if (!empty($_SESSION['user']) && !empty($_SESSION['user']['user_id'])) {
    $userId = $_SESSION['user']['user_id'];
    $stmt = $conn->prepare("SELECT name, email, address, city, zip, phone FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($dbName, $dbEmail, $dbAddress, $dbCity, $dbZip, $dbPhone);
    if ($stmt->fetch()) {
        $userData = [
            'name' => $dbName,
            'email' => $dbEmail,
            'address' => $dbAddress,
            'city' => $dbCity,
            'zip' => $dbZip,
            'phone' => $dbPhone
        ];
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form values
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $zip = trim($_POST['zip'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? '');

    // Validation
    if (empty($full_name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || empty($address) || empty($city) || empty($zip)
        || empty($phone) || empty($payment_method)) {
        $error = "Please fill in all required fields with valid values.";
    } elseif (empty($cart)) {
        $error = "Your cart is empty.";
    } else {
        // Insert order with status 'Pending'
        if ($userId === null) {
            $insertOrder = $conn->prepare("INSERT INTO orders 
                (user_id, full_name, email, address, city, zip, payment_method, order_total, status, created_at, updated_at, phone)
                VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW(), NOW(), ?)");
            $insertOrder->bind_param(
                "ssssssds",
                $full_name,
                $email,
                $address,
                $city,
                $zip,
                $payment_method,
                $cartTotal,
                $phone
            );
        } else {
            $insertOrder = $conn->prepare("INSERT INTO orders 
                (user_id, full_name, email, address, city, zip, payment_method, order_total, status, created_at, updated_at, phone)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW(), NOW(), ?)");
            $insertOrder->bind_param(
                "issssssds",
                $userId,
                $full_name,
                $email,
                $address,
                $city,
                $zip,
                $payment_method,
                $cartTotal,
                $phone
            );
        }

        if ($insertOrder->execute()) {
            $orderId = $insertOrder->insert_id;

            // Insert order items
            $insertItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
            foreach ($cart as $item) {
                $insertItem->bind_param(
                    "issid",
                    $orderId,
                    $item['product_id'],
                    $item['product_name'],
                    $item['quantity'],
                    $item['price']
                );
                $insertItem->execute();
            }
            $insertItem->close();

            unset($_SESSION['cart']);

            // Redirect to payment confirmation page (simulate payment success)
            header("Location: payment_confirm.php?order_id=" . $orderId);
            exit();
        } else {
            $error = "Failed to place the order. Please try again.";
        }
        $insertOrder->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Payment | Animz Max</title>
<link rel="stylesheet" href="checkout.css" />
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

<div class="container">
    <h1>Payment</h1>

    <?php if (!empty($error)): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if (!empty($cart)): ?>
        <h3>Order Summary</h3>
        <table>
            <thead>
                <tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>
            </thead>
            <tbody>
                <?php foreach ($cart as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo (int)$item['quantity']; ?></td>
                        <td>₹<?php echo number_format($item['price'], 2); ?></td>
                        <td>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="order_total">Total: ₹<?php echo number_format($cartTotal, 2); ?></p>
    <?php else: ?>
        <p>Your cart is empty.</p>
    <?php endif; ?>

    <form method="POST" action="payment.php" novalidate>
        <label for="full_name">Full Name *</label>
        <input type="text" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? $userData['name'] ?? ''); ?>" />

        <label for="email">Email *</label>
        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? $userData['email'] ?? ''); ?>" />

        <label for="address">Address *</label>
        <textarea id="address" name="address" rows="3" required><?php echo htmlspecialchars($_POST['address'] ?? $userData['address'] ?? ''); ?></textarea>

        <label for="city">City *</label>
        <input type="text" id="city" name="city" required value="<?php echo htmlspecialchars($_POST['city'] ?? $userData['city'] ?? ''); ?>" />

        <label for="zip">ZIP Code *</label>
        <input type="text" id="zip" name="zip" required value="<?php echo htmlspecialchars($_POST['zip'] ?? $userData['zip'] ?? ''); ?>" />

        <label for="phone">Phone Number *</label>
        <input type="text" id="phone" name="phone" required value="<?php echo htmlspecialchars($_POST['phone'] ?? $userData['phone'] ?? ''); ?>" />

        <label for="payment_method">Payment Method *</label>
        <select name="payment_method" id="payment_method" required>
            <option value="">-- Select Payment Method --</option>
            <option value="COD" <?php if (($_POST['payment_method'] ?? '') === 'COD') echo 'selected'; ?>>Cash on Delivery</option>
            <option value="UPI" <?php if (($_POST['payment_method'] ?? '') === 'UPI') echo 'selected'; ?>>UPI</option>
            <option value="NetBanking" <?php if (($_POST['payment_method'] ?? '') === 'NetBanking') echo 'selected'; ?>>Net Banking</option>
            <option value="Debit/Credit Card" <?php if (($_POST['payment_method'] ?? '') === 'Debit/Credit Card') echo 'selected'; ?>>Debit/Credit Card</option>
        </select>

        <div class="order_total">
            <h2>Order Total: ₹<?php echo number_format($cartTotal, 2); ?></h2>
        </div>

        <button type="submit" id="submitBtn">Pay Now</button>
    </form>

    <script>
    document.getElementById('submitBtn').addEventListener('click', function() {
        this.disabled = true;
        this.innerText = 'Processing Payment...';
    });
    </script>
</div>

</body>
</html>
