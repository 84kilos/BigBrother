<?php
require 'db.php';

$statusMessage = "";
$statusKind = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = trim((string)($_POST["firstName"] ?? ""));
    if ($firstName === "") {
        $statusMessage = "First name cannot be blank.";
        $statusKind = "error";
    }
    $lastName = trim((string)($_POST["lastName"] ?? ""));
    if ($statusMessage === "" && $lastName === "") {
        $statusMessage = "Last name cannot be blank.";
        $statusKind = "error";
    }

    $username = trim((string)($_POST["username"] ?? ""));
    if ($statusMessage === "" && $username === "") {
        $statusMessage = "Username cannot be blank.";
        $statusKind = "error";
    }
    $rawPassword = (string)($_POST["password"] ?? "");
    if ($statusMessage === "" && strlen($rawPassword) < 8) {
        $statusMessage = "Password must be at least 8 characters.";
        $statusKind = "error";
    }
    $role = strtolower(trim((string)($_POST["role"] ?? "")));

    if ($statusMessage === "" && !in_array($role, ["teacher", "student"], true)) {
        $statusMessage = "Invalid role selected.";
        $statusKind = "error";
    }

    if ($statusMessage === "") {
        $passwordHash = password_hash($rawPassword, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, username, password_hash, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $firstName, $lastName, $username, $passwordHash, $role);

        $ok = $stmt->execute();
        if ($ok) {
            $statusMessage = "Registered successfully!";
            $statusKind = "success";
        } elseif ($stmt->errno === 1062) {
            $statusMessage = "Username already taken.";
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

$statusMessageSafe = htmlspecialchars($statusMessage, ENT_QUOTES, "UTF-8");

$basePath = rtrim(dirname($_SERVER["SCRIPT_NAME"] ?? ""), "/\\");
$loginHref = $basePath . "/index.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="login-body">
        <div class="bb-login-hero">
            <span class="bb-icon-eye bb-icon-eye--fluid" data-follow-mouse data-follow-enabled="false" role="img" aria-label="Camera lens"></span>
        </div>
        <div class="login-container">
            <h2>Register Account</h2>
            <form action="" method="POST">
                <label for="firstName">First Name:</label>
                <input type="text" id="firstName" name="firstName" required>
                <label for="lastName">Last Name:</label>
                <input type="text" id="lastName" name="lastName" required>

                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>

                <label>Account type:</label>
                <div class="bb-toggle-row" role="radiogroup" aria-label="Account type">
                    <input type="radio" id="role-teacher" name="role" value="teacher" required>
                    <label for="role-teacher">Teacher</label>

                    <input type="radio" id="role-student" name="role" value="student" checked>
                    <label for="role-student">Student</label>
                </div>

                <button type="submit">Register</button>
            </form>
            <a class="bb-auth-link" href="<?php echo $loginHref ?>">Back to Login</a>
            <p id="statusMessage" class="<?php echo $statusClass; ?>" aria-live="polite"><?php echo $statusMessageSafe; ?></p>
        </div>
    </div>
    <script src="assets/js/eye-follow.js" defer></script>
</body>
</html>
