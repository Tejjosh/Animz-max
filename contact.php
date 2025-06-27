<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "animzmax_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Use real_escape_string to avoid SQL injection
    $name    = $conn->real_escape_string(trim($_POST["name"]));
    $email   = $conn->real_escape_string(trim($_POST["email"]));
    $message = $conn->real_escape_string(trim($_POST["message"]));

    // Insert message into the database
    $sql = "INSERT INTO contact_messages (name, email, message) VALUES ('$name', '$email', '$message')";

   if ($conn->query($sql) === TRUE) {
    // Redirect to avoid duplicate submissions on refresh
    header("Location: contact.php?success=1");
    exit();
} else {
    $error = "Error: " . $conn->error;
}

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Contact Us | Animz Max</title>
  <link rel="stylesheet" href="contact.css" />
</head>
<body>
   <header id="header">
    <div class="logo">
      <a href="index.html" class="logo">Animz Max</a>
    </div>
    <nav>
      <a href="search.php" title="Search">🔍</a>
      <a href="product.php">🧥</a>
      <a href="cart.php" title="Cart">🛒<span id="cart-count"></span></a>
      <a href="loginpage.php" title="Login">👤</a>
    </nav>
  </header>
  <section class="contact-section">
    <h1>Contact Us</h1>
    <p class="contact-text anime-style">
      <strong>Let's talk anime and fashion!</strong><br /><br />
      Whether you're battling sizing issues like a shonen hero or need help tracking your order faster than a ninja, we’ve got your back!<br /><br />
      Questions about our handcrafted gear, custom anime prints, or just want to say <em>"Sugoi!"</em> to our designs? Drop us a message — no summoning jutsu required!<br /><br />
      Our support team (powered by caffeine and fan theories) is here to help you, Monday to Saturday.<br /><br />
      <strong>Join the Animz Max guild — where style meets the spirit of anime.</strong>
    </p>

<?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
  <p style="color:green;">Message saved successfully!</p>
<?php elseif (!empty($error)): ?>
  <p style="color:red;"><?php echo $error; ?></p>
<?php endif; ?>

    <form class="contact-form" method="POST" action="contact.php">
      <input type="text" name="name" placeholder="Your Name" required />
      <input type="email" name="email" placeholder="Your Email" required />
      <textarea name="message" rows="5" placeholder="Your Message" required></textarea>
      <button type="submit">Send Message</button>
    </form>
  </section>

  <footer>
    <p>&copy; 2025 Animz Max. All rights reserved.</p>
  </footer>
</body>
</html>
