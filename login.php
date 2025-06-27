<?php 
session_start();

// ✅ Redirect if already logged in
if (isset($_SESSION['user_name'])) {
    header("Location: loginpage.php");
    exit();
}

$rememberedIdentifier = $_COOKIE['remember_identifier'] ?? '';
$error = "";

// ✅ Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "animzmax_db";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $identifier = $conn->real_escape_string($_POST['identifier']);
    $inputPass = $_POST['password'];
    $remember = isset($_POST['remember']);

    // Lookup user by name or email
    $sql = "SELECT * FROM users WHERE name = '$identifier' OR email = '$identifier' LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($inputPass, $user['password'])) {
            // ✅ Set session variables
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_id'] = $user['user_id']; // make sure your column is 'user_id'
            $_SESSION['user'] = $user; // optional: for profile access
            $_SESSION['login_time'] = time();

            // ✅ Set remember me cookie
            if ($remember) {
                setcookie('remember_identifier', $identifier, time() + (30 * 24 * 60 * 60), "/");
            } else {
                setcookie('remember_identifier', '', time() - 3600, "/");
            }

            // ✅ Link guest cart if exists
            if (isset($_SESSION['guest_cart_id'])) {
                $guestCartId = $_SESSION['guest_cart_id'];
                $userId = $_SESSION['user_id'];
                $conn->query("UPDATE checkouts SET user_id = '$userId' WHERE session_cart_id = '$guestCartId'");
                unset($_SESSION['guest_cart_id']);
            }

            $conn->close();
            header("Location: loginpage.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Login - Animz Max</title>
  <link rel="stylesheet" href="login.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <header>
    <a class="logo" href="index.html">Animz Max</a>
  </header>

  <main>
    <div class="login-container">
      <h1>Login to Animz Max</h1>

      <?php if (!empty($error)): ?>
        <p class="error-message"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <form method="POST" action="login.php" autocomplete="off">
        <label for="identifier">Username or Email:</label>
        <input 
          type="text" 
          id="identifier" 
          name="identifier" 
          value="<?= htmlspecialchars($rememberedIdentifier) ?>" 
          required 
          autocomplete="username"
        />

        <label for="password">Password:</label>
        <div class="password-wrapper">
          <input 
            type="password" 
            id="password" 
            name="password" 
            required 
            autocomplete="current-password"
          />
          <button type="button" class="toggle-password" aria-label="Toggle password visibility" onclick="togglePassword()">Show</button>
        </div>

        <div class="remember-me">
          <input 
            type="checkbox" 
            id="remember" 
            name="remember" 
            <?= $rememberedIdentifier ? 'checked' : '' ?>
          />
          <label for="remember">Remember me</label>
        </div>

        <button type="submit" class="btn-login">Login</button>
      </form>

      <div class="links">
        <p>Don't have an account? <a href="signup.php">Sign up</a></p>
        <p><a href="forgot.php">Forgot Password?</a></p>
      </div>
    </div>
  </main>

  <script>
    function togglePassword() {
      const passwordInput = document.getElementById("password");
      const toggleButton = document.querySelector(".toggle-password");
      if (passwordInput.type === "password") {
        passwordInput.type = "text";
        toggleButton.textContent = "Hide";
      } else {
        passwordInput.type = "password";
        toggleButton.textContent = "Show";
      }
    }
  </script>
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
</html>
