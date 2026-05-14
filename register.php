<?php 
include('db.php'); 
session_start(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Residents Profiling</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: white; border: 1px solid #e5e7eb; padding: 30px; border-radius: 24px; width: 400px; text-align: center;  }
        .icon-circle { width: 60px; height: 60px; background: #00a65a; border-radius: 50%; display: flex; justify-content: center; align-items: center; margin: 0 auto 15px; color: white; font-size: 30px; }
        h2 { margin: 0; font-size: 22px; color: #1a1f2e; }
        .subtitle { color: #64748b; font-size: 14px; margin-bottom: 25px; }
        
        label { display: block; text-align: left; font-weight: bold; font-size: 13px; margin-bottom: 4px; color: #1e293b; }
        .input-group { position: relative; width: 100%; margin-bottom: 4px; }
        
        input, select { 
            width: 100%; padding: 12px; background: #f1f5f9; border: 1px solid #e2e8f0; 
            border-radius: 10px; box-sizing: border-box; font-size: 14px; outline: none; 
        }
        input:focus { border-color: #111827; background: #fff; }

        .eye-icon { position: absolute; right: 15px; top: 12px; color: #94a3b8; cursor: pointer; z-index: 10; }

        
        .btn-register { 
            background: #1a1a1a; color: white; border: none; padding: 14px; 
            width: 100%; border-radius: 12px; font-size: 16px; font-weight: bold; 
            cursor: pointer; margin-top: 10px; 
        }

        
        .btn-blue { background: #3f41e6; color: white; border: none; padding: 12px 40px; border-radius: 12px; cursor: pointer; text-decoration: none; display: inline-block; font-weight: bold; margin-top: 10px; }
        
        .footer { margin-top: 20px; font-size: 14px; color: #64748b; }
        .footer a { color: #111827; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<?php 
if(isset($_POST['register'])): 
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Check if username already exists
    $check_user = mysqli_query($conn, "SELECT username FROM users WHERE username = '$user'");

    if(mysqli_num_rows($check_user) > 0): ?>
        <div class="card">
            <div class="icon-circle" style="background:#f59e0b;">!</div>
            <h2>Username Taken</h2>
            <p>The username <strong>"<?php echo htmlspecialchars($user); ?>"</strong> is already in use.</p>
            <br>
            <a href="register.php" class="btn-blue">Choose Another</a>
        </div>

    <?php elseif($password !== $confirm_password): ?>
        <div class="card">
            <div class="icon-circle" style="background:#ef4444;">✕</div>
            <h2>Passwords do not match!</h2>
            <p>Please ensure both password fields are identical.</p>
            <br>
            <a href="register.php" class="btn-blue">Try Again</a>
        </div>

    <?php else: 
        // 2. Success: Proceed with Registration
        $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (full_name, role, username, password) VALUES ('$full_name', '$role', '$user', '$hashed_pass')";
        
        if(mysqli_query($conn, $query)): ?>
            <div class="card">
                <div class="icon-circle">✓</div>
                <h2>Account Created!</h2>
                <p>Your official account has been successfully registered.</p>
                <br><br>
                <a href="login.php" class="btn-blue">Login Now</a>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="icon-circle" style="background:#ef4444;">✕</div>
                <h2>System Error</h2>
                <p>Something went wrong. Please contact your administrator.</p>
                <br>
                <a href="register.php" class="btn-blue">Go Back</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

<?php else: ?>
    <div class="card">
        <div class="icon-circle">👤</div>
        <h2>Create Account</h2>
        <p class="subtitle">Join the Residents' Profiling System</p>

        <form method="POST">
            <label>Full Name</label>
            <div class="input-group">
                <input type="text" name="full_name" placeholder="e.g. Juan Dela Cruz" required>
            </div>

            <label>Select Role</label>
            <div class="input-group">
                <select name="role" required>
                    <option value="" disabled selected>Select your position</option>
                    <option value="Secretary">Secretary</option>
                    <option value="Barangay Captain">Barangay Captain</option>
                </select>
            </div>

            <label>Username</label>
            <div class="input-group">
                <input type="text" name="username" placeholder="Choose a username" required>
            </div>

            <label>Password</label>
            <div class="input-group">
                <input type="password" id="pass" name="password" placeholder="Create password" required>
                <i class="fa-regular fa-eye eye-icon" onclick="togglePassword('pass', this)"></i>
            </div>

            <label>Confirm Password</label>
            <div class="input-group">
                <input type="password" id="confirm_pass" name="confirm_password" placeholder="Repeat password" required>
                <i class="fa-regular fa-eye eye-icon" onclick="togglePassword('confirm_pass', this)"></i>
            </div>

            <button type="submit" name="register" class="btn-register">Create Account</button>
        </form>

        <div class="footer">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
<?php endif; ?>

<script>
    function togglePassword(inputId, icon) {
        const passwordInput = document.getElementById(inputId);
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            icon.classList.replace("fa-eye", "fa-eye-slash");
        } else {
            passwordInput.type = "password";
            icon.classList.replace("fa-eye-slash", "fa-eye");
        }
    }
</script>

</body>
</html>















