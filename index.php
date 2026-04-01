<?php
session_start();
require_once __DIR__ . '/config/db.php';

// API endpoint routing
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    $module = $_GET['api'];

    switch ($module) {
        case 'members':
            require_once __DIR__ . '/controllers/memberController.php';
            $c = new MemberController();
            echo json_encode($c->handleRequest());
            break;
        case 'savings':
            require_once __DIR__ . '/controllers/savingsController.php';
            $c = new SavingsController();
            echo json_encode($c->handleRequest());
            break;
        case 'loans':
            require_once __DIR__ . '/controllers/loanController.php';
            $c = new LoanController();
            echo json_encode($c->handleRequest());
            break;
        case 'users':
            require_once __DIR__ . '/controllers/userController.php';
            $c = new UserController();
            echo json_encode($c->handleRequest());
            break;
        case 'settings':
            require_once __DIR__ . '/controllers/settingsController.php';
            $c = new SettingsController();
            echo json_encode($c->handleRequest());
            break;
        case 'leaves':
            require_once __DIR__ . '/controllers/leaveController.php';
            $c = new LeaveController();
            echo json_encode($c->handleRequest());
            break;
        case 'dashboard':
            require_once __DIR__ . '/controllers/memberController.php';
            require_once __DIR__ . '/controllers/savingsController.php';
            require_once __DIR__ . '/controllers/loanController.php';
            $mc = new MemberController(); 
            $sc = new SavingsController(); 
            $lc = new LoanController();
            $_GET['action'] = 'stats';
            $mStats = $mc->handleRequest();
            $sStats = $sc->handleRequest();
            $lStats = $lc->handleRequest();
            echo json_encode(['success' => true, 'members' => $mStats['data'], 'savings' => $sStats['data'], 'loans' => $lStats['data']]);
            break;
        default:
            echo json_encode(['error' => 'Unknown module']);
    }
    exit;
}

// Handle login/logout/register
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
                // Legacy plain-text password; accept it and upgrade to a hash.
                $isValidPassword = true;
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateStmt->bind_param('si', $newHash, $user['id']);
                $updateStmt->execute();
                $updateStmt->close();
            }
        }

        if ($user && $isValidPassword) {
            if ($selectedRole === 'admin' && $user['role'] !== 'admin') {
                $login_error = 'Please login with an admin account';
            } elseif ($selectedRole === 'user' && $user['role'] === 'admin') {
                $login_error = 'Please login with a user account';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
                header('Location: index.php');
                exit;
            }
        } else {
            $login_error = 'Invalid username or password';
        }
    } elseif ($_POST['action'] === 'register') {
        $username = trim($_POST['username'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($username === '' || $full_name === '' || $password === '' || $confirm_password === '') {
            $register_error = 'All fields are required.';
        } elseif ($password !== $confirm_password) {
            $register_error = 'Passwords do not match.';
        } elseif (strlen($password) < 4) {
            $register_error = 'Password must be at least 4 characters.';
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $register_error = 'Username already exists.';
            } else {
                $stmt->close();
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $role = 'officer';
                $insertStmt = $conn->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
                $insertStmt->bind_param('ssss', $username, $hash, $full_name, $role);
                if ($insertStmt->execute()) {
                    $insertStmt->close();
                    header('Location: index.php?registered=1');
                    exit;
                }
                $insertStmt->close();
                $register_error = 'Registration failed. Please try again.';
            }
            $stmt->close();
        }
    }
}

if (isset($_GET['registered'])) {
    $register_success = 'Registration successful. You can now log in.';
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Check auth
$logged_in = isset($_SESSION['user_id']);
$page = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SACCO Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>

<?php if (!$logged_in): ?>
<!-- LOGIN PAGE -->
<div class="login-screen">
    <div class="login-bg">
        <div class="login-orb orb-1"></div>
        <div class="login-orb orb-2"></div>
        <div class="login-orb orb-3"></div>
        <div class="grid-overlay"></div>
    </div>
    <div class="login-container">
        <div class="login-brand">
            <div class="brand-icon">
                <svg viewBox="0 0 40 40" fill="none"><circle cx="20" cy="20" r="18" stroke="currentColor" stroke-width="2"/><path d="M12 20h16M20 12v16" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/><path d="M14 14l12 12M26 14L14 26" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" opacity="0.4"/></svg>
            </div>
            <div class="brand-text">
                <span class="brand-name">SACCO<strong>Pro</strong></span>
                <span class="brand-tagline">Cooperative Management System</span>
            </div>
        </div>

        <?php if ($page === 'register'): ?>
        <div class="login-card">
            <div class="login-header">
                <h1>Create an account</h1>
                <p>Register and then sign in to continue</p>
            </div>

            <?php if (!empty($register_error)): ?>
            <div class="alert alert-error">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <?= htmlspecialchars($register_error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <input type="hidden" name="action" value="register">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 4a4 4 0 100 8 4 4 0 000-8zM2 16a6 6 0 0112 0H2z" clip-rule="evenodd"/></svg>
                        <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required autocomplete="name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                        <input type="text" id="username" name="username" placeholder="Choose a username" required autocomplete="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                        <input type="password" id="password" name="password" placeholder="Enter password" required autocomplete="new-password">
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm password</label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required autocomplete="new-password">
                    </div>
                </div>
                <button type="submit" class="btn-login">
                    <span>Register</span>
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
            </form>

            <div class="login-hint">
                <span>Already have an account?</span>
                <a href="index.php">Sign in</a>
            </div>
        </div>
        <?php else: ?>
        <div class="login-card">
            <div class="login-header">
                <h1>Welcome back</h1>
                <p>Sign in to your account to continue</p>
            </div>

            <?php if (!empty($register_success)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($register_success) ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($login_error)): ?>
            <div class="alert alert-error">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <?= htmlspecialchars($login_error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                        <input type="text" id="username" name="username" placeholder="Enter username" required autocomplete="username">
                    </div>
                </div>
                <div class="form-group">
                    <label for="role">Login as</label>
                    <select id="role" name="role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                        <input type="password" id="password" name="password" placeholder="Enter password" required autocomplete="current-password">
                        <button type="button" class="toggle-pwd" onclick="togglePassword()">
                            <svg id="eye-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-login">
                    <span>Sign In</span>
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
            </form>

            <div class="login-hint">
                <span>Demo credentials:</span>
                <code>admin / admin123</code>
            </div>
            <div class="login-hint">
                <span>Don’t have an account?</span>
                <a href="index.php?page=register">Register now</a>
            </div>
        </div>
        <?php endif; ?>

        <div class="login-footer">© 2025 SACCOPro · Secure Cooperative Management</div>
    </div>
</div>

<?php else: ?>
<!-- MAIN APP -->
<div class="app-layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <div class="brand-icon-sm">
                    <svg viewBox="0 0 40 40" fill="none"><circle cx="20" cy="20" r="18" stroke="currentColor" stroke-width="2"/><path d="M12 20h16M20 12v16" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
                </div>
                <div>
                    <span class="brand-name-sm">SACCO<strong>Pro</strong></span>
                    <span class="brand-sub">Management System</span>
                </div>
            </div>
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
            </button>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-label">Main</div>
            <a href="?page=dashboard" class="nav-item <?= $page === 'dashboard' ? 'active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M3 4a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 8a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H4a1 1 0 01-1-1v-4zm8-8a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V4zm0 8a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
                <span>Dashboard</span>
            </a>

            <div class="nav-section-label">Operations</div>
            <a href="?page=members" class="nav-item <?= $page === 'members' ? 'active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zm8 0a3 3 0 11-6 0 3 3 0 016 0zm-4.07 11c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                <span>Members</span>
                <span class="nav-badge" id="members-count">–</span>
            </a>
            <a href="?page=savings" class="nav-item <?= $page === 'savings' ? 'active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/></svg>
                <span>Savings</span>
            </a>
            <a href="?page=loans" class="nav-item <?= $page === 'loans' ? 'active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                <span>Loans</span>
                <span class="nav-badge pending" id="loans-pending">–</span>
            </a>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <div class="nav-section-label">Admin</div>
            <a href="?page=users" class="nav-item <?= $page === 'users' ? 'active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 9a3 3 0 11-6 0 3 3 0 016 0zm8 0a3 3 0 11-6 0 3 3 0 016 0zm-4.07 11c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" clip-rule="evenodd"/></svg>
                <span>Users</span>
            </a>
            <a href="?page=settings" class="nav-item <?= $page === 'settings' ? 'active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10.325 4.317a1 1 0 00-1.65 0l-.709 1.02a7.004 7.004 0 00-1.692.978l-1.2-.478a1 1 0 00-1.19.45l-1 1.732a1 1 0 00.45 1.34l1.2.478a6.978 6.978 0 000 1.956l-1.2.478a1 1 0 00-.45 1.34l1 1.732a1 1 0 001.19.45l1.2-.478a7.019 7.019 0 001.692.978l.709 1.02a1 1 0 001.65 0l.709-1.02a7.002 7.002 0 001.692-.978l1.2.478a1 1 0 001.19-.45l1-1.732a1 1 0 00-.45-1.34l-1.2-.478a6.978 6.978 0 000-1.956l1.2-.478a1 1 0 00.45-1.34l-1-1.732a1 1 0 00-1.19-.45l-1.2.478a7.004 7.004 0 00-1.692-.978l-.709-1.02zM10 13a3 3 0 110-6 3 3 0 010 6z"/></svg>
                <span>Settings</span>
            </a>
            <a href="?page=leaves" class="nav-item <?= $page === 'leaves' ? 'active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 5a2 2 0 100 4 2 2 0 000-4zM12 5a2 2 0 100 4 2 2 0 000-4zM5 12a2 2 0 100 4 2 2 0 000-4zm10 0a2 2 0 100 4 2 2 0 000-4z" clip-rule="evenodd"/></svg>
                <span>Leave Requests</span>
            </a>
            <a href="?page=reports" class="nav-item <?= $page === 'reports' ? 'active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 3a1 1 0 011-1h10a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V3zm2 2v10h8V5H6zm2 2h4v2H8V7z" clip-rule="evenodd"/></svg>
                <span>Reports</span>
            </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 2)) ?></div>
                <div class="user-details">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <span class="user-role"><?= htmlspecialchars($_SESSION['user_role']) ?></span>
                </div>
            </div>
            <a href="?logout=1" class="btn-logout" title="Sign out">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/></svg>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <button class="mobile-menu-btn" onclick="toggleSidebar()">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                </button>
                <div class="breadcrumb">
                    <span class="breadcrumb-home">SACCOPro</span>
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                    <span class="breadcrumb-current"><?= ucfirst($page) ?></span>
                </div>
            </div>
            <div class="topbar-right">
                <div class="topbar-date"><?= date('D, d M Y') ?></div>
                <div class="topbar-user">
                    <div class="user-avatar-sm"><?= strtoupper(substr($_SESSION['user_name'], 0, 2)) ?></div>
                </div>
            </div>
        </div>

        <div class="page-content" id="page-content">
            <?php
                if (in_array($page, ['users','settings','reports','leaves']) && $_SESSION['user_role'] !== 'admin') { $page = 'dashboard'; }
                switch ($page) {
                    case 'members': include __DIR__ . '/views/members.php'; break;
                    case 'savings': include __DIR__ . '/views/savings.php'; break;
                    case 'loans': include __DIR__ . '/views/loans.php'; break;
                    case 'users': include __DIR__ . '/views/users.php'; break;
                    case 'settings': include __DIR__ . '/views/settings.php'; break;
                    case 'leaves': include __DIR__ . '/views/leaves.php'; break;
                    case 'reports': include __DIR__ . '/views/reports.php'; break;
                    default: include __DIR__ . '/views/dashboard.php'; break;
                }
            ?>
        </div>
    </main>
</div>
<?php endif; ?>

<!-- Global Modal -->
<div class="modal-overlay" id="modal-overlay" onclick="closeModal(event)">
    <div class="modal" id="modal">
        <div class="modal-header">
            <h3 id="modal-title">Modal</h3>
            <button onclick="closeModal()" class="modal-close">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </button>
        </div>
        <div class="modal-body" id="modal-body"></div>
    </div>
</div>

<!-- Toast notifications -->
<div id="toast-container"></div>

<script src="assets/script.js"></script>
</body>
</html>