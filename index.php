<?php
require 'db.php';

session_start();

$statusMessage = "";
$statusKind = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim((string)($_POST["email"] ?? ""));
    $password = (string)($_POST["password"] ?? "");

    $stmt = $conn->prepare("SELECT id, full_name, email, password_hash, role FROM users WHERE email = ?");

    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($userId, $fullName, $userEmail, $hashedPassword, $userRole);
            $stmt->fetch();
            if (password_verify($password, $hashedPassword)) {
                $_SESSION["id"] = $userId;
                $_SESSION["full_name"] = $fullName;
                $_SESSION["email"] = $userEmail;
                $_SESSION["role"] = $userRole;

                $basePath = rtrim(dirname($_SERVER["SCRIPT_NAME"] ?? ""), "/\\");
                if ($userRole === "teacher") {
                    header("Location: {$basePath}/teacher/dashboard.php");
                } else {
                    header("Location: {$basePath}/student/dashboard.php");
                }
                exit;
            }
        }
    }

    $statusMessage = "Invalid credentials";
    $statusKind = "error";
}

$basePath = rtrim(dirname($_SERVER["SCRIPT_NAME"] ?? ""), "/\\");
$registerHref = $basePath . "/register.php";

$statusClass = "bb-status";
if ($statusKind === "error") {
    $statusClass .= " bb-status--error";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BigBrother | Login</title>
  <script src="assets/theme.js"></script>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="auth-wrapper">
    <div class="auth-layout">
      <section class="hero-card glass-card">
        <div>
          <div class="brand-badge">
            <span class="logo-dot"></span>
            BigBrother Attendance System
          </div>
        </div>

        <div class="hero-copy">
          <h1>Attendance tracking tool for both teachers and students.</h1>
          <p>
          Teachers can mark attendance quickly, and students can track their progress in real time.
          </p>

          <ul class="feature-list">
            <li>Teacher dashboard for live attendance marking</li>
            <li>Student dashboard for history and attendance percentage</li>
            <li>Contact page for teachers and students</li>
          </ul>
        </div>

      </section>

      <section class="form-card glass-card">
        <h2>Welcome back</h2>
        <p class="soft-text">Use your email and password to sign in.</p>

        <?php if ($statusMessage !== ""): ?>
          <div class="<?php echo htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo htmlspecialchars($statusMessage, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <form class="form-grid" action="index.php" method="POST">
          <div class="form-group">
            <label for="email">Email</label>
            <input class="text-input" type="email" id="email" name="email" placeholder="teacher@example.com" required>
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <input class="text-input" type="password" id="password" name="password" placeholder="Enter your password" required>
          </div>

          <div class="btn-row">
            <button class="btn btn-primary" type="submit" name="login">Login</button>
            <a class="btn btn-secondary" href="<?php echo htmlspecialchars($registerHref, ENT_QUOTES, 'UTF-8'); ?>">Create Account</a>
          </div>
        </form>

        <div class="helper-links">
          <a href="<?php echo htmlspecialchars($registerHref, ENT_QUOTES, 'UTF-8'); ?>">Need an account?</a>
        </div>
      </section>
    </div>
  </div>
</body>
</html>
