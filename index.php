<?php
require 'db.php';

$statusMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars($_POST["username"] ?? "");
    $password = htmlspecialchars($_POST["password"] ?? "");

    $stmt = $conn->prepare("SELECT id, password_hash, role FROM users where username = ?");

    $stmt->bind_param("sssss", $firstName, $lastName, $username, $password_hash, $role);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $hashed_password, $user_role);
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            $_SESSION["user_id"] = $id;
            if ($user_role === "teacher"){
                header("Location: teacher/dashboard.php");
            } else {
                header("Location: student/dashboard.php");
            }
            exit;
        }
    }
    $statusMessage = "Invalid credentials";
}

$basePath = rtrim(dirname($_SERVER["SCRIPT_NAME"] ?? ""), "/\\");
$registerHref = $basePath . "/register.php";
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
            <form action="/login" method="POST">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>

                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>

                <button type="submit">Login</button>
                
            </form>
            <a class="bb-auth-link" href=" bb-status--error">Register Account</a>
        </div>
    </div>
    <script src="assets/js/eye-follow.js" defer></script>
</body>
</html>
