<?php
session_start();

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize message
$message = '';
$messageColor = 'red';

// Check for success message after redirect
if (isset($_SESSION['reset_success'])) {
    $message = $_SESSION['reset_success'];
    $messageColor = 'green';
    unset($_SESSION['reset_success']);
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database configuration
    $host = 'localhost';
    $db   = 'animzmax_db';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    // Create PDO connection
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        $message = 'Database connection failed.';
    }

    if (empty($message)) {
        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            $message = 'Email is required.';
        } else {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $message = 'Email not found in our records.';
            } else {
                // Generate token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Save token and expiry
                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
                $stmt->execute([$token, $expires, $email]);

                // Full reset link
                $baseURL = 'http://localhost/animzmax2/'; // Update to your actual project URL if hosted online
                $resetLink = $baseURL . "reset-password-form.php?token=$token";

                // Send reset email
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'sandbox.smtp.mailtrap.io';
                    $mail->SMTPAuth = true;
                    $mail->Port = 2525;
                    $mail->Username = 'd4edf37cd83c2a';
                    $mail->Password = '8ee3e34e8bc8cb';

                    $mail->setFrom('no-reply@animzmax.com', 'Animz Max');
                    $mail->addAddress($email, $user['name']);

                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset - Animz Max';
                    $mail->Body = generateResetEmail($user['name'], $resetLink);

                    $mail->send();
                    logEmailStatus($pdo, $email, 'Password Reset - Animz Max', 'Success');

                    // ✅ Set success message and redirect to avoid resubmission
                    $_SESSION['reset_success'] = 'Password reset link has been sent to your email.';
                    header('Location: forgot.php');
                    exit;

                } catch (Exception $e) {
                    logEmailStatus($pdo, $email, 'Password Reset - Animz Max', 'Failed: ' . $mail->ErrorInfo);
                    $message = 'Failed to send email: ' . $mail->ErrorInfo;
                }
            }
        }
    }
}

// Email body function
function generateResetEmail($name, $link) {
    return "
        <h2>Hello $name,</h2>
        <p>We received a request to reset your Animz Max account password.</p>
        <p>Please click the link below to reset your password:</p>
        <p><a href='$link'>$link</a></p>
        <p><strong>This link will expire in 1 hour.</strong></p>
        <p>If you did not request a password reset, please ignore this email or contact our support team.</p>
        <br>
        <p>Thank you,<br><strong>Animz Max Team</strong></p>
    ";
}

// Log email status
function logEmailStatus($pdo, $email, $subject, $status) {
    $stmt = $pdo->prepare("INSERT INTO email_logs (user_email, subject, status) VALUES (?, ?, ?)");
    $stmt->execute([$email, $subject, $status]);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Forgot Password | Animz Max</title>
  <link rel="stylesheet" href="lsfstyles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<header role="banner">
  <div class="logo">
    <a href="index.html" class="logo">Animz Max</a>
  </div>
</header>

<div class="container">
  <form class="form" method="POST" action="">
    <h2>Forgot Password</h2>

    <label for="email">Email</label>
    <input type="email" id="email" name="email" placeholder="Enter your email" required />

    <button type="submit">Reset Password</button>

    <div class="links">
      <a href="loginpage.php">Back to Login</a>
    </div>

    <?php if (!empty($message)): ?>
      <div id="message" style="margin-top: 15px; color: <?= $messageColor ?>;">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>
  </form>
</div>

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
      </ul>
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

</body>
</html>
