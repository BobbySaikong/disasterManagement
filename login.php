<?php
include 'dbconnect.php';
session_start();
$status = "";
$message = "";

// for security
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
  $email = $_POST['email'] ?? '';
  $password = $_POST['password'] ?? '';
  $hashedPassword = sha1($password);

  $sqllogin = "SELECT * FROM tbl_users WHERE user_email = '$email' AND user_password = '$hashedPassword'";
  $result = $conn->query($sqllogin);

  if ($result->num_rows > 0) {
    $userdata = $result->fetch_assoc();

    $_SESSION['user_id'] = $userdata['user_id'];
    $_SESSION['user_name'] = $userdata['user_name'];
    $_SESSION['user_email'] = $userdata['user_email'];
    $_SESSION['user_role'] = $userdata['user_role'];



    switch ($userdata['user_role']) {
      case 'villager':
        header('Location: dashboard/villager/villager_dashboard.php');
        break;
      case 'ketuakampung':
        header('Location: dashboard/ketuakampung/ketuakampung_dashboard.php');
        break;
      case 'penghulu':
        header('Location: dashboard/penghulu/penghulu_dashboard.php');
        break;
      case 'pejabatdaerah':
        header('Location: dashboard/pejabatdaerah/pejabatdaerah_dashboard.php');
        break;
      case 'kplbhq':
        header('Location: dashboard/kplbhq/kplb_dashboard.php');
        break;
    }
    exit();
  } else {
    $status = "error";
    $message = "User not found or incorrect password";
  }
}
?>

<!DOCTYPE html>
<html>

<head>
  <title>DVMD Login</title>
  <link rel="stylesheet" href="css/style.css">
</head>

<header>
  <div class="header-left">
    <img src="assets/logo.png" class="logo">
    <h1>Digital Village Management Dashboard

    </h1>
  </div>
  <nav>
    <a href="#">About Us</a>
    <a href="#">Contact</a>
    
  </nav>
</header>

<body>

  <div class="card">
    <h2>DVMD Login</h2>

    <form method="POST">
      <div class="input-group">
        <label>Email</label>
        <input type="email" name="email" required>
      </div>

      <div class="input-group">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>

      <button class="btn" name="login">Login</button>
    </form>


    <?php
    if (!empty($message)) {
      if ($status === "success") {
        echo '<div class="success">' . htmlspecialchars($message) . '</div>';
      } else {
        echo '<div class="error">' . htmlspecialchars($message) . '</div>';
      }
    }
    ?>

    <div class="link">
      No account? <a href="signup.php">Sign up</a>
    </div>
  </div>

  <!-- Memo Carousel -->
  <div class="memo-carousel">
    <div class="memo-slide active">
      <p>Langkah-Langkah Terbakar!</p>
      <img src="assets/langkah terbakar.jpg" alt="Announcement 1">
      
    </div>
    <div class="memo-slide">
      <p>Dilarang membakar sampah dikawasan rumah!</p>
      <img src="assets/membakarsampah.jpg" alt="Announcement 2">
      
    </div>
    <div class="memo-slide">
      <p>Langkah-Langkah Banjir!</p>
      <img src="assets/LANGKAH-KESELAMATAN-DI-MUSIM-BANJIR-2.jpeg" alt="Announcement 3">
      
    </div>
  </div>


</body>

<script>
  let slides = document.querySelectorAll('.memo-slide');
  let current = 0;

  function showSlide(index) {
    slides.forEach(slide => slide.classList.remove('active'));
    slides[index].classList.add('active');
  }

  function nextSlide() {
    current = (current + 1) % slides.length;
    showSlide(current);
  }

  showSlide(current);
  setInterval(nextSlide, 3000); // change every 3 seconds
</script>


</html>