<?php
include('db.php');
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Barangay Captain') {
    header("Location: login.php");
    exit();
}

$errors = [];
$success = '';

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $allowed_roles = ['Secretary', 'Barangay Captain'];

    if ($full_name === '') $errors[] = 'Full name is required.';
    if (!in_array($role, $allowed_roles, true)) $errors[] = 'Invalid role selected.';
    if ($username === '') $errors[] = 'Username is required.';
    if (strlen($password) < 4) $errors[] = 'Password must be at least 4 characters.';
    if ($password !== $confirm_password) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $safe_user = mysqli_real_escape_string($conn, $username);
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$safe_user'");
        if ($check && mysqli_num_rows($check) > 0) {
            $errors[] = 'Username is already taken.';
        } else {
            $safe_name = mysqli_real_escape_string($conn, $full_name);
            $safe_role = mysqli_real_escape_string($conn, $role);
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $insert = "INSERT INTO users (full_name, role, username, password) VALUES ('$safe_name', '$safe_role', '$safe_user', '$hashed')";
            if (mysqli_query($conn, $insert)) {
                $action_desc = mysqli_real_escape_string($conn, "Captain created new user: $full_name ($role)");
                mysqli_query($conn, "INSERT INTO logs (action) VALUES ('$action_desc')");
                $success = "User \"$full_name\" created successfully!";
            } else {
                $errors[] = 'Failed to create user. Please try again.';
            }
        }
    }
}

// Handle Delete User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $delete_id = (int)$_POST['delete_user_id'];
    // Prevent deleting yourself
    if ($delete_id === (int)($_SESSION['user_id'] ?? 0)) {
        $errors[] = 'You cannot delete your own account.';
    } elseif ($delete_id > 0) {
        $del_user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT full_name FROM users WHERE id = '$delete_id'"));
        mysqli_query($conn, "DELETE FROM users WHERE id = '$delete_id'");
        $action_desc = mysqli_real_escape_string($conn, "Captain deleted user: " . ($del_user['full_name'] ?? "ID#$delete_id"));
        mysqli_query($conn, "INSERT INTO logs (action) VALUES ('$action_desc')");
        $success = 'User deleted successfully.';
    }
}

// Fetch all users
$users_query = mysqli_query($conn, "SELECT id, full_name, role, username FROM users ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Barangay Captain</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --sidebar-navy: #1e293b; --accent-blue: #2563eb; --logo-orange: #ff9800; --text-gray: #64748b; }
        body { font-family: 'Inter', sans-serif; margin: 0; display: flex; height: 100vh; background: #f1f5f9; overflow: hidden; }

        .main-container { flex: 1; overflow-y: auto; display: flex; flex-direction: column; }
        .content-body { padding: 16px 20px 20px; }
        .panel { background: white; border: 1px solid #e2e8f0; padding: 24px; border-radius: 20px; margin-bottom: 20px; }

        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .panel-header h3 { margin: 0; font-size: 18px; color: #1e293b; }

        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full-width { grid-column: span 2; }
        label { font-weight: 600; color: #1e293b; font-size: 13px; }
        input, select {
            padding: 11px 14px; border: 1px solid #e2e8f0; border-radius: 10px;
            font-family: inherit; font-size: 14px; outline: none; background: #f8fafc;
        }
        input:focus, select:focus { border-color: var(--accent-blue); background: white; }

        .btn-save {
            background: var(--accent-blue); color: white; padding: 12px 28px; border: none;
            border-radius: 12px; font-weight: 600; cursor: pointer; font-size: 14px;
            display: inline-flex; align-items: center; gap: 8px; margin-top: 8px;
        }
        .btn-save:hover { background: #1d4ed8; }

        .error-box { background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 12px; margin-bottom: 16px; font-size: 14px; }
        .success-box { background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 12px; margin-bottom: 16px; font-size: 14px; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 14px 15px; border-bottom: 2px solid #e5e7eb; color: var(--text-gray); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 14px 15px; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #334155; }

        .role-badge {
            padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;
        }
        .role-captain { background: #fef3c7; color: #92400e; }
        .role-secretary { background: #dbeafe; color: #1e40af; }

        .btn-delete {
            background: none; border: none; color: #ef4444; cursor: pointer; font-weight: 600;
            font-size: 13px; display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px;
            border-radius: 8px;
        }
        .btn-delete:hover { background: #fee2e2; }

        .you-badge { background: #f0fdf4; color: #166534; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; margin-left: 8px; }

        .password-wrap { position: relative; }
        .password-wrap input { width: 100%; box-sizing: border-box; padding-right: 40px; }
        .eye-toggle { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8; font-size: 14px; }

        .empty-state { text-align: center; padding: 40px; color: var(--text-gray); }

        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .form-group.full-width { grid-column: span 1; }
        }
    </style>
</head>
<body>

<?php include_once('left_navbar.php'); ?>

<div class="main-container">
    <header class="top-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="margin:0;">Manage Users</h2>
            <p style="margin:0; color: var(--text-gray);">Create and manage system accounts</p>
        </div>
    </header>

    <div class="content-body">

        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <?php foreach ($errors as $err): ?>
                    <div><?php echo htmlspecialchars($err); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="success-box"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Add New User Form -->
        <div class="panel">
            <div class="panel-header">
                <h3><i class="fa-solid fa-user-plus" style="color: var(--accent-blue); margin-right: 8px;"></i>Add New User</h3>
            </div>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" placeholder="e.g. Juan Dela Cruz" required>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" required>
                            <option value="" disabled selected>Select role</option>
                            <option value="Secretary">Secretary</option>
                            <option value="Barangay Captain">Barangay Captain</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" placeholder="Choose a username" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <div class="password-wrap">
                            <input type="password" name="password" id="addPassword" placeholder="Create password" required minlength="4">
                            <i class="fa-regular fa-eye eye-toggle" onclick="togglePass('addPassword', this)"></i>
                        </div>
                    </div>
                    <div class="form-group full-width">
                        <label>Confirm Password</label>
                        <div class="password-wrap">
                            <input type="password" name="confirm_password" id="addConfirmPassword" placeholder="Repeat password" required>
                            <i class="fa-regular fa-eye eye-toggle" onclick="togglePass('addConfirmPassword', this)"></i>
                        </div>
                    </div>
                </div>
                <button type="submit" name="add_user" class="btn-save"><i class="fa-solid fa-plus"></i> Create User</button>
            </form>
        </div>

        <!-- Users List -->
        <div class="panel">
            <div class="panel-header">
                <h3><i class="fa-solid fa-users" style="color: var(--accent-blue); margin-right: 8px;"></i>System Users</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users_query && mysqli_num_rows($users_query) > 0): ?>
                        <?php while ($u = mysqli_fetch_assoc($users_query)): ?>
                            <?php $is_self = ((int)$u['id'] === (int)($_SESSION['user_id'] ?? 0)); ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($u['full_name']); ?></strong>
                                    <?php if ($is_self): ?><span class="you-badge">You</span><?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                <td>
                                    <?php
                                        $badge_class = ($u['role'] === 'Barangay Captain') ? 'role-captain' : 'role-secretary';
                                    ?>
                                    <span class="role-badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($u['role']); ?></span>
                                </td>
                                <td>
                                    <a href="edit_user.php?id=<?php echo (int)$u['id']; ?>" style="background: none; border: none; color: var(--accent-blue); cursor: pointer; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; border-radius: 8px; text-decoration: none;">
                                        <i class="fa-solid fa-pen"></i> Edit
                                    </a>
                                    <?php if (!$is_self): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                            <input type="hidden" name="delete_user_id" value="<?php echo (int)$u['id']; ?>">
                                            <button type="submit" class="btn-delete">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">No users found.</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script>
    function togglePass(inputId, icon) {
        const input = document.getElementById(inputId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
</script>
</body>
</html>
