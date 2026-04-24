<?php
session_start();
$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BigBrother | Login</title>
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
          <h1>Track attendance with a clean, modern dashboard.</h1>
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

        <?php if ($error): ?>
          <div class="notice-box"><?php echo htmlspecialchars($error); ?></div>
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
            <a class="btn btn-secondary" href="register.php">Create Account</a>
          </div>
        </form>

        <div class="helper-links">
          <a href="register.php">Need an account?</a>
        </div>
      </section>
    </div>
  </div>
</body>
</html>
