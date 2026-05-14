<?php 
include('db.php'); 
session_start(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Pulantubig Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: sans-serif; background-color: #f1f5f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; border: 1px solid #e5e7eb; padding: 40px; border-radius: 24px; width: 380px; text-align: center;  }
        .logo { width: 70px; margin-bottom: 10px; }
        h2 { margin: 0; font-size: 22px; }
        .subtitle { color: #555; font-size: 14px; margin-bottom: 30px; }
        label { display: block; text-align: left; font-weight: bold; font-size: 13px; margin-bottom: 4px; }
        .input-box { position: relative; width: 100%; margin-bottom: 20px; }
        input, select { width: 100%; padding: 12px; background: #e8ecef; border: none; border-radius: 10px; box-sizing: border-box; font-size: 14px; outline: none; }
        .eye-icon { position: absolute; right: 15px; top: 12px; color: #aaa; cursor: pointer; }
        .btn-login { background: #1a1a1a; color: white; border: none; padding: 15px; width: 100%; border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer;  }

        .footer { margin-top: 20px; font-size: 14px; }
    </style>
</head>
<body>

<div class="card">
    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRFPOnNDg4Y5AhoHbUTqz-33jP3WX2ehWimhg&s" class="logo" alt="Barangay Logo"> 
    <h2>Barangay Pulantubig</h2>
    <div class="subtitle">Residents' Profiling System</div>

    <form method="POST" action="">
        <label>Username</label>
        <div class="input-box">
            <input type="text" name="username" placeholder="Enter username" required>
        </div>

        <label>Password</label>
        <div class="input-box">
            <input type="password" id="pass" name="password" placeholder="Enter password" required>
            <i class="fa-regular fa-eye eye-icon" onclick="toggle()"></i>
        </div>

        <button type="submit" name="login" class="btn-login">Login</button>
    </form>

    <div class="footer">
        Don't have an account? <a href="register.php" style="color: #4a4ae6; text-decoration: none; font-weight: bold;">Create Account</a>
    </div>
</div>

<script>
    function toggle() {
        var x = document.getElementById("pass");
        var icon = document.querySelector(".eye-icon");
        if (x.type === "password") {
            x.type = "text";
            icon.classList.replace("fa-eye", "fa-eye-slash");
        } else {
            x.type = "password";
            icon.classList.replace("fa-eye-slash", "fa-eye");
        }
    }
</script>

<?php
if(isset($_POST['login'])){
    // Sanitize input
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $pass = $_POST['password'];

    // Check database for user
    $query = "SELECT * FROM users WHERE username='$user'";
    $res = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($res);

    if($data && password_verify($pass, $data['password'])){
        $role = $data['role'];
        // Store user info in session
        $_SESSION['user_id'] = $data['id'];
        $_SESSION['username'] = $data['username'];
        $_SESSION['role'] = $data['role'];
        $_SESSION['full_name'] = $data['full_name'];

        // Redirect based on role
        if($role == "Secretary") {
            header("Location: secretary_dashboard.php");
        } else if ($role == "Barangay Captain") {
            header("Location: captain_dashboard.php");
        }
        exit();
    } else {
        echo "<script>alert('Invalid username or password.');</script>";
    }
}
?>

</body>
</html>














