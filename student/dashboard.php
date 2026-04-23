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
</body>
</html>