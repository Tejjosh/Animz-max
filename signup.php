<?php
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "animzmax_db";

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$name = $email = $password = $confirm_password = "";
$errors = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize inputs
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm-password'];

    // Validate inputs
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    // Check if email already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Email is already registered.";
        }
        $stmt->close();
    }

    // Insert user if no errors
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $hashed_password);

        if ($stmt->execute()) {
             $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
$mail->Host = 'sandbox.smtp.mailtrap.io';
$mail->SMTPAuth = true;
$mail->Port = 2525;
$mail->Username = 'd4edf37cd83c2a';
$mail->Password = '8ee3e34e8bc8cb';

        // Sender & recipient
        $mail->setFrom('no-reply@animzmax.com', 'Animz');
        $mail->addAddress($email, $name);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to Animz Max!';
        $mail->Body    = generateWelcomeEmail($name);

        $mail->send();
        logEmailStatus($conn, $email, 'Welcome to Animz Max!', 'Success');
                    $success = "Registration successful! A welcome email has been sent to you. You can now <a href='loginpage.php'>login</a>.";

    } catch (Exception $e) {
        logEmailStatus($conn, $email, 'Welcome to Animz Max!', 'Failed: ' . $mail->ErrorInfo);
    }

            // sendWelcomeEmail($conn, $email, $name);
            $name = $email = $password = $confirm_password = "";
        } else {
            $errors[] = "Error during registration. Please try again.";
        }
        $stmt->close();
    }
}  

// Generate email body
function generateWelcomeEmail($name) {
    return "
        <h2>Welcome to Animz Max, $name!</h2>
        <p>Thank you for registering with us. We are thrilled to have you as part of our community!</p>
        <p>At Animz Max, we bring you the latest and greatest in anime-inspired fashion and accessories. From exclusive T-shirts to cozy hoodies, your style journey starts here.</p>
        <p>To get you started, here are some tips:</p>
        <ul>
            <li>Explore our <a href='http://yourdomain.com/products'>exclusive collections</a> designed just for anime lovers like you.</li>
            <li>Check out our ongoing promotions and discounts.</li>
            <li>Join our community on social media for news, giveaways, and sneak peeks.</li>
        </ul>
        <p>If you have any questions or need help, feel free to <a href='http://yourdomain.com/contact'>contact our support team</a>. We're here for you!</p>
        <p>Thank you again for joining Animz Max. We can't wait to see you rock your anime style!</p>
        <p>Happy shopping!<br><strong>The Animz Max Team</strong></p>
    ";
}

// Log email status
function logEmailStatus($conn, $email, $subject, $status) {
    $stmt = $conn->prepare("INSERT INTO email_logs (user_email, subject, status) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $subject, $status);
    $stmt->execute();
    $stmt->close();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Sign Up</title>
<link rel="stylesheet" href="lsfstyles.css" >
</head>
<body>
<header role="banner">
  <div class="logo">
      <a href="index.html" class="logo">Animz Max</a>
  </div>
</header>

<div class="container">
  <form class="form" action="" method="POST">
    <h2>Sign Up</h2>

    <?php if (!empty($errors)): ?>
      <div class="error-messages" style="color:red;">
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="success-message" style="color:green;">
        <?= $success ?>
      </div>
    <?php endif; ?>

    <label for="name">Full Name</label>
    <input type="text" id="name" name="name" placeholder="Enter your full name" required value="<?= htmlspecialchars($name) ?>" />

    <label for="email">Email</label>
    <input type="email" id="email" name="email" placeholder="Enter your email" required value="<?= htmlspecialchars($email) ?>" />

    <label for="password">Password</label>
    <input type="password" id="password" name="password" placeholder="Create a password" required />

    <label for="confirm-password">Confirm Password</label>
    <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm your password" required />

    <button type="submit">Sign Up</button>

    <div class="links">
      <a href="loginpage.php">Login</a>
    </div>
  </form>
</div>
</body>
<footer class="footer">
  <div class="footer-container">
    <div class="footer-logo">
      <a href="index.html" class="footer-logo">Animz Max</a>
    </div>
    <div class="footer-links">

      <ul>
        <li><a href="index.html">Home</a></li>
        <li><a href="product.php">Products</a></li>
        <li><a href="contact.php">Contact</a></li>
        <li><a href="privacy.html">Privacy Policy</a></li>
          <li><a href="terms.html">Terms of Use</a></li>
          <li><a href="returns.html">Returns</a></li>
        <ul>
        </div>

    <div class="footer-social">
      <h4>Follow Us</h4>
      <div class="social-icons">
        <a href="#"><i class="fab fa-instagram"></i></a>
        <a href="#"><i class="fab fa-x-twitter"></i></a>
        <a href="#"><i class="fab fa-facebook"></i></a>
      </div>
    </div>
  </div>

  <div class="footer-bottom">
    <p>&copy; 2025 Animz Max. All rights reserved.</p>
  </div>
</footer>
</html>
