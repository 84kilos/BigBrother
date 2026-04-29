<?php
require 'db.php';

$statusMessage = "";
$statusKind = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim((string)($_POST["full_name"] ?? ""));
    if ($full_name === "") {
        $statusMessage = "Full name cannot be blank.";
        $statusKind = "error";
    }

    $email = trim((string)($_POST["email"] ?? ""));
    if ($statusMessage === "" && $email === "") {
        $statusMessage = "Email cannot be blank.";
        $statusKind = "error";
    }
    $password = (string)($_POST["password"] ?? "");
    $confirm_password = (string)($_POST["confirm_password"] ?? "");
    if ($statusMessage === "" && strlen($password) < 8) {
        $statusMessage = "Password must be at least 8 characters.";
        $statusKind = "error";
    }
    if ($statusMessage === "" && $password !== $confirm_password) {
        $statusMessage = "Passwords do not match.";
        $statusKind = "error";
    }
    $role = strtolower(trim((string)($_POST["role"] ?? "")));

    if ($statusMessage === "" && !in_array($role, ["teacher", "student"], true)) {
        $statusMessage = "Invalid role selected.";
        $statusKind = "error";
    }

    if ($statusMessage === "") {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $full_name, $email, $passwordHash, $role);

        $ok = $stmt->execute();
        if ($ok) {
            $statusMessage = "Registered successfully!";
            $statusKind = "success";
        } elseif ($stmt->errno === 1062) {
            $statusMessage = "Email already registered.";
            $statusKind = "error";
        } else {
            $statusMessage = "Registration failed.";
            $statusKind = "error";
        }
    }
}

$statusClass = "bb-status";
if ($statusKind === "success") {
    $statusClass .= " bb-status--success";
} elseif ($statusKind === "error") {
    $statusClass .= " bb-status--error";
}

$basePath = rtrim(dirname($_SERVER["SCRIPT_NAME"] ?? ""), "/\\");
$loginHref = $basePath . "/index.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BigBrother | Register</title>
  <script src="assets/theme.js"></script>
  <link rel="stylesheet" href="assets/style.css">
  <link rel="stylesheet" href="assets/eye.css">
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
        <div class="bb-login-hero">
            <span class="bb-icon-eye bb-icon-eye--fluid" data-follow-mouse data-follow-enabled="false" role="img" aria-label="Camera lens"></span>
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

        <?php if ($statusMessage !== ""): ?>
          <div class="<?php echo htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($statusMessage, ENT_QUOTES, 'UTF-8'); ?>
          </div>
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
            <a class="btn btn-secondary" href="<?php echo htmlspecialchars($loginHref, ENT_QUOTES, 'UTF-8'); ?>">Back to Login</a>
          </div>
        </form>
      </section>

    </div>
  </div>
  <script src="assets/eye-follow.js" defer></script>
</body>
</html>
