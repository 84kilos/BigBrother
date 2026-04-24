<?php
session_start();
$success = $_SESSION['register_success'] ?? '';
$error = $_SESSION['register_error'] ?? '';
unset($_SESSION['register_success'], $_SESSION['register_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BigBrother | Register</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="auth-wrapper">
    <div class="auth-layout">

      <!-- Left Side -->
      <section class="hero-card glass-card">
        <div class="brand-badge">
          <span class="logo-dot"></span>
          BigBrother
        </div>

        <div class="hero-copy">
          <h1>Create your account</h1>
          <p>
            Register as a teacher or student to access the attendance system and manage your activity.
          </p>
        </div>
      </section>

      <!-- Form Side -->
      <section class="form-card glass-card">
        <h2>Create account</h2>
        <p class="soft-text">Fill out the form below to get started.</p>

        <?php if ($success): ?>
          <div class="notice-box"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
          <div class="notice-box"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form class="form-grid" action="register.php" method="POST">
          <div class="form-row two">
            <div class="form-group">
              <label for="full_name">Full Name</label>
              <input class="text-input" type="text" id="full_name" name="full_name" placeholder="Enter your name" required>
            </div>

            <div class="form-group">
              <label for="role">Role</label>
              <select class="select-input" id="role" name="role" required>
                <option value="student">Student</option>
                <option value="teacher">Teacher</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="email">Email</label>
            <input class="text-input" type="email" id="email" name="email" placeholder="Enter your email" required>
          </div>

          <div class="form-row two">
            <div class="form-group">
              <label for="password">Password</label>
              <input class="text-input" type="password" id="password" name="password" placeholder="Create a password" required>
            </div>

            <div class="form-group">
              <label for="confirm_password">Confirm Password</label>
              <input class="text-input" type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
            </div>
          </div>

          <div class="btn-row">
            <button class="btn btn-primary" type="submit" name="register">Register</button>
            <a class="btn btn-secondary" href="index.php">Back to Login</a>
          </div>
        </form>
      </section>

    </div>
  </div>
</body>
</html>
