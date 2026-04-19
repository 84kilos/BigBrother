<?php
require 'db.php';

session_start();

$statusMessage = "";
$statusKind = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim((string)($_POST["username"] ?? ""));
    $password = (string)($_POST["password"] ?? "");

    $stmt = $conn->prepare("SELECT user_id, password_hash, role FROM users WHERE username = ?");

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($userId, $hashedPassword, $userRole);
        $stmt->fetch();
        if (password_verify($password, $hashedPassword)) {
            $_SESSION["user_id"] = $userId;
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
    <title>Login</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="login-body">
        <div class="big-title">
            <h1>BIG BROTHER</h1>
            <h2>Attendence tool for teachers and students</h2>
        </div>
        <div class="bb-login-hero">
            <span class="bb-icon-eye bb-icon-eye--fluid" data-follow-mouse data-follow-enabled="false" role="img" aria-label="Camera lens"></span>
        </div>
        <div class="login-container">
            <h2>Login</h2>
            <p id="statusMessage" class="<?php echo $statusClass; ?>" aria-live="polite"><?php echo $statusMessage; ?></p>
            <form action="" method="POST">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>

                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>

                <button type="submit">Login</button>
                
            </form>
            <a class="bb-auth-link" href="<?php echo $registerHref; ?>">Register Account</a>
        </div>
    </div>
    <script src="assets/js/eye-follow.js" defer></script>
</body>
</html>
