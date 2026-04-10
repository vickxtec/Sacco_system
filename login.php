<?php
session_start();
require_once __DIR__ . '/config/db.php';

$login_error = '';
$register_error = '';
$register_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $selectedRole = $_POST['role'] ?? 'user';

        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        $isValidPassword = false;
        if ($user) {
            if (password_verify($password, $user['password'])) {
                $isValidPassword = true;
            } elseif ($password === $user['password']) {
                $isValidPassword = true;
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateStmt->bind_param('si', $newHash, $user['id']);
                $updateStmt->execute();
            }
        }

        if ($isValidPassword && $user['role'] === $selectedRole) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            
            header('Location: index.php?page=dashboard');
            exit;
        } else {
            $login_error = 'Invalid username, password, or role.';
        }
    } elseif ($_POST['action'] === 'register') {
        $fullname = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['reg_username'] ?? '');
        $password = $_POST['reg_password'] ?? '';
        $role = $_POST['reg_role'] ?? 'user';

        if (!$fullname || !$username || !$password) {
            $register_error = 'All fields are required.';
        } else {
            $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM users WHERE username = ?");
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            if ($result['cnt'] > 0) {
                $register_error = 'Username already exists.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $insertStmt = $conn->prepare("INSERT INTO users (fullname, username, password, role) VALUES (?, ?, ?, ?)");
                $insertStmt->bind_param('ssss', $fullname, $username, $hashedPassword, $role);

                if ($insertStmt->execute()) {
                    $register_success = 'Registration successful! Please log in.';
                } else {
                    $register_error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}

if (isset($_SESSION['user_id'])) {
    header('Location: index.php?page=dashboard');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SACCO Management System - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #0d0f14 0%, #13161d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e8ecf4;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 30px;
            background: #1a1e28;
            border-radius: 12px;
            border: 1px solid #2a2f3e;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .login-header p {
            color: #a0a8be;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #e8ecf4;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            background: #222736;
            border: 1px solid #2a2f3e;
            border-radius: 6px;
            color: #e8ecf4;
            font-size: 14px;
            transition: all 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4f8ef7;
            background: #222736;
            box-shadow: 0 0 0 3px rgba(79, 142, 247, 0.1);
        }
        .btn-login {
            width: 100%;
            padding: 10px;
            background: linear-gradient(135deg, #4f8ef7, #3b7de8);
            border: none;
            border-radius: 6px;
            color: white;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 12px;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #3b7de8, #2d6ed9);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 142, 247, 0.4);
        }
        .alert {
            padding: 10px 12px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 13px;
        }
        .alert-error {
            background: #2d1010;
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        .alert-success {
            background: #0f2e22;
            border: 1px solid rgba(62, 207, 142, 0.3);
            color: #86efac;
        }
        .tab-buttons {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 1px solid #2a2f3e;
        }
        .tab-btn {
            flex: 1;
            padding: 10px;
            background: transparent;
            border: none;
            color: #a0a8be;
            cursor: pointer;
            font-weight: 500;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }
        .tab-btn.active {
            color: #4f8ef7;
            border-bottom-color: #4f8ef7;
        }
        .form-section { display: none; }
        .form-section.active { display: block; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🏦 SACCO</h1>
            <p>Savings & Credit Cooperative Organization</p>
        </div>

        <div class="tab-buttons">
            <button type="button" class="tab-btn active" onclick="switchTab('login')">Sign In</button>
            <button type="button" class="tab-btn" onclick="switchTab('register')">Register</button>
        </div>

        <!-- Login Form -->
        <div id="login" class="form-section active">
            <?php if ($login_error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($login_error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Login As</label>
                    <select name="role" required>
                        <option value="user">Member</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <button type="submit" class="btn-login">Sign In</button>
            </form>
        </div>

        <!-- Register Form -->
        <div id="register" class="form-section">
            <?php if ($register_error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($register_error) ?></div>
            <?php endif; ?>
            <?php if ($register_success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($register_success) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="register">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" required>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="reg_username" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="reg_password" required>
                </div>
                <div class="form-group">
                    <label>Register As</label>
                    <select name="reg_role" required>
                        <option value="user">Member</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <button type="submit" class="btn-login">Register</button>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(tab).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
