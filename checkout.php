<?php 
session_start();
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

// Default form values
$full_name = $email = $address = $city = $zip = $phone = $payment_method = "";

// Autofill user info if logged in
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $zip = trim($_POST['zip'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? '');

    if (empty($full_name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || empty($address) || empty($city) || empty($zip)
        || empty($phone) || empty($payment_method)) {
        $error = "Please fill in all required fields with valid values.";
    } elseif (empty($cart)) {
        $error = "Your cart is empty.";
    } else {
        // 1. Check Stock Availability for each product in cart
        $stockIssue = false;
        foreach ($cart as $item) {
            $productId = $item['product_id'];
            $orderedQty = $item['quantity'];

            $stockStmt = $conn->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
            $stockStmt->bind_param("i", $productId);
            $stockStmt->execute();
            $stockStmt->bind_result($availableStock);
            $stockStmt->fetch();
            $stockStmt->close();

            if ($orderedQty > $availableStock) {
                $stockIssue = true;
                $error = "Insufficient stock for product: " . htmlspecialchars($item['product_name']) . ". Available quantity: $availableStock.";
                break;
            }
        }

        if (!$stockIssue) {
            // 2. Check for duplicate recent orders (your existing logic)
            $check = $conn->prepare("SELECT order_id FROM orders WHERE email = ? AND address = ? AND created_at >= NOW() - INTERVAL 5 MINUTE");
            $check->bind_param("ss", $email, $address);
            $check->execute();
            $check->store_result();

            $allowOrder = true;

            if ($check->num_rows > 0) {
                $check->bind_result($existingOrderId);
                while ($check->fetch()) {
                    $itemStmt = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                    $itemStmt->bind_param("i", $existingOrderId);
                    $itemStmt->execute();
                    $result = $itemStmt->get_result();
                    $existingItems = [];
                    while ($row = $result->fetch_assoc()) {
                        $existingItems[$row['product_id']] = $row['quantity'];
                    }
                    $itemStmt->close();

                    $currentItems = [];
                    foreach ($cart as $item) {
                        $currentItems[$item['product_id']] = $item['quantity'];
                    }

                    if ($existingItems == $currentItems) {
                        $allowOrder = false;
                        break;
                    }
                }
            }
            $check->close();

            if (!$allowOrder) {
                $error = "You've already placed a similar order with the same products recently.";
            } else {
                // Begin transaction
                $conn->begin_transaction();

                try {
                    // 3. Insert into orders table
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

                    if (!$insertOrder->execute()) {
                        throw new Exception("Failed to place order.");
                    }

                    $orderId = $insertOrder->insert_id;
                    $insertOrder->close();

             // 4. Insert order items & deduct stock
$insertItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
$updateStockStmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?");

foreach ($cart as $item) {
    // Insert order item
    $insertItem->bind_param(
        "issid",
        $orderId,
        $item['product_id'],
        $item['product_name'],
        $item['quantity'],
        $item['price']
    );
    if (!$insertItem->execute()) {
        throw new Exception("Failed to insert order item.");
    }

    // Deduct stock
    // Note the param types: quantity (int), product_id (string)
    $updateStockStmt->bind_param("is", $item['quantity'], $item['product_id']);
    if (!$updateStockStmt->execute()) {
        throw new Exception("Failed to update stock.");
    }
}

$insertItem->close();
$updateStockStmt->close();

                    // 5. Optional: Update user profile address/phone if empty
                    if ($userId) {
                        if (!isset($_SESSION['order_count_updated_for_order_' . $orderId])) {
                            $checkStmt = $conn->prepare("SELECT address, phone, zip, city FROM users WHERE user_id = ?");
                            $checkStmt->bind_param("i", $userId);
                            $checkStmt->execute();
                            $checkStmt->bind_result($dbAddress, $dbPhone, $dbZip, $dbCity);
                            $checkStmt->fetch();
                            $checkStmt->close();

                            if (empty($dbAddress) || empty($dbPhone) || empty($dbZip) || empty($dbCity)) {
                                $updateStmt = $conn->prepare("UPDATE users SET address = ?, phone = ?, zip = ?, city = ? WHERE user_id = ?");
                                $updateStmt->bind_param("ssssi", $address, $phone, $zip, $city, $userId);
                                $updateStmt->execute();
                                $updateStmt->close();
                            }

                            $_SESSION['order_count_updated_for_order_' . $orderId] = true;
                        }
                    }

                    // Commit transaction
                    $conn->commit();

                    // 6. Send confirmation email with PHPMailer (same as your code)
                    $phpmailer = new PHPMailer(true);
                    try {
                        $phpmailer->isSMTP();
                        $phpmailer->Host = 'sandbox.smtp.mailtrap.io';
                        $phpmailer->SMTPAuth = true;
                        $phpmailer->Port = 2525;
                        $phpmailer->Username = 'd4edf37cd83c2a';
                        $phpmailer->Password = '8ee3e34e8bc8cb';

                        $phpmailer->setFrom('noreply@animzmax.com', 'Animz Max');
                        $phpmailer->addAddress($email, $full_name);
                        $phpmailer->Subject = 'Order Confirmation - Animz Max';
                        $phpmailer->isHTML(true);

                        $itemList = "";
                        foreach ($cart as $item) {
                            $itemList .= "<li>" . htmlspecialchars($item['product_name']) . " - Quantity: " . (int)$item['quantity'] . "</li>";
                        }
$phpmailer->Body = "
<div style='font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; margin: auto; padding: 30px; border: 1px solid #d4d4d4; border-radius: 10px; background-color: #f9f9f9; color: #333;'>
    <h1 style='color: #00573f; text-align: center; font-weight: 700; margin-bottom: 0;'>Order Confirmation</h1>
    <p style='font-size: 16px; margin-top: 5px;'>Dear <strong>{$full_name}</strong>,</p>
    <p style='font-size: 16px; line-height: 1.5;'>
        Thank you for your recent order with <strong>Animz Max</strong>. We are pleased to inform you that your order has been successfully processed. Below you will find the details of your purchase.
    </p>

    <section style='background: #ffffff; padding: 20px; border-radius: 8px; margin-top: 20px;'>
        <h2 style='color: #00573f; border-bottom: 2px solid #00573f; padding-bottom: 6px; font-weight: 600;'>Order Summary</h2>
        <table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>
            <tr>
                <td style='padding: 10px; border: 1px solid #ddd; font-weight: 600;'>Order ID</td>
                <td style='padding: 10px; border: 1px solid #ddd;'>{$orderId}</td>
            </tr>
            <tr>
                <td style='padding: 10px; border: 1px solid #ddd; font-weight: 600;'>Order Total</td>
                <td style='padding: 10px; border: 1px solid #ddd;'>₹" . number_format($cartTotal, 2) . "</td>
            </tr>
            <tr>
                <td style='padding: 10px; border: 1px solid #ddd; font-weight: 600;'>Payment Method</td>
                <td style='padding: 10px; border: 1px solid #ddd;'>{$payment_method}</td>
            </tr>
        </table>
    </section>

    <section style='background: #ffffff; padding: 20px; border-radius: 8px; margin-top: 20px;'>
        <h2 style='color: #00573f; border-bottom: 2px solid #00573f; padding-bottom: 6px; font-weight: 600;'>Items Ordered</h2>
        <ul style='list-style-type: none; padding-left: 0; margin-top: 10px; font-size: 16px;'>
            {$itemList}
        </ul>
    </section>

    <section style='background: #ffffff; padding: 20px; border-radius: 8px; margin-top: 20px;'>
        <h2 style='color: #00573f; border-bottom: 2px solid #00573f; padding-bottom: 6px; font-weight: 600;'>Shipping Address</h2>
        <p style='font-size: 16px; margin-top: 10px; line-height: 1.5;'>
            {$address}<br>
            {$city} - {$zip}
        </p>
    </section>

    <hr style='margin: 30px 0; border-color: #ccc;'>

    <p style='font-size: 16px; line-height: 1.5;'>
        Should you have any questions or require further assistance, please do not hesitate to contact our customer support team at <a href='mailto:support@animzmax.com' style='color: #00573f; text-decoration: none;'>support@animzmax.com</a>.
    </p>

    <p style='font-size: 16px; line-height: 1.5; margin-bottom: 0;'>
        We truly appreciate your business and look forward to serving you again soon.
    </p>

    <p style='font-size: 14px; color: #777; text-align: center; margin-top: 40px;'>
        This is an automated message. Please do not reply directly to this email.
    </p>
</div>";


                        $phpmailer->send();
                    } catch (Exception $e) {
                        // Log or ignore email sending error
                    }

                    unset($_SESSION['cart']);
                    header("Location: confirmation.php?order_id=" . $orderId);
                    exit();

                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Failed to place the order: " . $e->getMessage();
                }
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Checkout | Animz Max</title>
  <link rel="stylesheet" href="checkout.css" />
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      background-color: #f4f4f4;
      color: #333;
    }

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

    .container {
      max-width: 800px;
      margin: 30px auto;
      background: white;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    h1 {
      text-align: center;
      color: #00573f;
    }

    label {
      display: block;
      margin-top: 15px;
      font-weight: bold;
    }

    input,
    textarea,
    select {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    table th,
    table td {
      padding: 12px;
      border: 1px solid #ddd;
      text-align: center;
    }

    table th {
      background-color: #00573f;
      color: white;
    }

    .order_total {
      margin-top: 20px;
      font-size: 20px;
      font-weight: bold;
      text-align: right;
    }

    button {
      background-color: #00573f;
      color: white;
      padding: 12px 20px;
      border: none;
      border-radius: 4px;
      font-size: 16px;
      cursor: pointer;
      display: block;
      margin: 20px auto 0;
      transition: background-color 0.3s ease;
    }

    button:disabled {
      background-color: #aaa;
      cursor: not-allowed;
    }

    p.error {
      color: red;
      text-align: center;
      font-weight: bold;
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

  <div class="container">
    <h1>Checkout</h1>

    <?php if (!empty($error)): ?>
      <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if (!empty($cart)): ?>
      <h3>Cart Summary</h3>
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
      <p style="text-align:center; font-weight:bold; margin-top:20px;">Your cart is empty.</p>
    <?php endif; ?>

    <form method="POST" action="checkout.php" id="checkout-form" novalidate>
      <label for="full_name">Full Name *</label>
      <input type="text" id="full_name" name="full_name" required
        value="<?php echo htmlspecialchars($_POST['full_name'] ?? $userData['name'] ?? ''); ?>" />

      <label for="email">Email *</label>
      <input type="email" id="email" name="email" required
        value="<?php echo htmlspecialchars($_POST['email'] ?? $userData['email'] ?? ''); ?>" />

      <label for="address">Address *</label>
      <textarea id="address" name="address" rows="3"
        required><?php echo htmlspecialchars($_POST['address'] ?? $userData['address'] ?? ''); ?></textarea>

      <label for="city">City *</label>
      <input type="text" id="city" name="city" required
        value="<?php echo htmlspecialchars($_POST['city'] ?? $userData['city'] ?? ''); ?>" />

      <label for="zip">ZIP Code *</label>
      <input type="text" id="zip" name="zip" required
        value="<?php echo htmlspecialchars($_POST['zip'] ?? $userData['zip'] ?? ''); ?>" />

      <label for="phone">Phone Number *</label>
      <input type="text" id="phone" name="phone" required
        value="<?php echo htmlspecialchars($_POST['phone'] ?? $userData['phone'] ?? ''); ?>" />

      <label for="payment_method">Payment Method *</label>
      <select name="payment_method" id="payment_method" required>
        <option value="">-- Select Payment Method --</option>
        <?php
        $methods = ['COD' => 'Cash on Delivery', 'UPI' => 'UPI', 'NetBanking' => 'Net Banking', 'Debit/Credit Card' => 'Debit/Credit Card'];
        $selectedMethod = $_POST['payment_method'] ?? '';
        foreach ($methods as $val => $label) {
          $sel = ($val === $selectedMethod) ? 'selected' : '';
          echo "<option value=\"" . htmlspecialchars($val) . "\" $sel>$label</option>";
        }
        ?>
      </select>

      <div class="order_total">
        <h2>Order Total: ₹<?php echo number_format($cartTotal, 2); ?></h2>
      </div>

      <button type="submit" id="submitBtn" <?php echo empty($cart) ? 'disabled' : ''; ?>>Place Order</button>
    </form>

    <script>
      document.getElementById('checkout-form').addEventListener('submit', function (e) {
        const btn = document.getElementById('submitBtn');
        if (btn.disabled) {
          e.preventDefault(); // Prevent multiple submits if already disabled
        } else {
          btn.disabled = true;
          btn.innerText = "Processing...";
        }
      });
    </script>
  </div>

</body>

</html>
