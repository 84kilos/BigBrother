<?php  
    session_start();  
    if (!isset($_SESSION["user_id"])) {  
        header("Location: index.php");  
        exit;  
    }  
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <h2>Welcome! You are logged in.</h2>
    <a class="bb-auth-link" href="../logout.php">Logout</a>
    <div class="class-previews">
        <div class="class-preview">
            <div class="class-preview-name">
                <p>The Super demon of evil learning class of death</p>
            </div>
            <div class="class-preview-count">
                <p>20 students</p>
            </div>
            <div class="class-preview-goto">
                <p>→</p>
            </div>
        </div>
        <div class="class-preview">
            <p>hey</p>
        </div>
        <div class="class-preview">
            <p>hey</p>
        </div>
        <div class="class-preview">
            <p>hey</p>
        </div>
        <div class="class-preview">
            <p>hey</p>
        </div>
    </div>
</body>
</html>
