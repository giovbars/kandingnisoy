<?php
require_once 'config/config.php';
require_once 'config/functions.php';
require_once 'includes/activity-logger.php'; 

// uncomment on deployment
/*
require_once $_SERVER['DOCUMENT_ROOT'] . '/test/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/test/config/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/test/includes/activity-logger.php';
*/

if (isLoggedIn()) {
    switch($_SESSION['role']) {
        case 'admin':
            redirect('/app/admin/dashboard.php');
            break;
        case 'manager':
            redirect('/app/manager/dashboard.php');
            break;
        case 'user':
            redirect('/app/user/dashboard.php');
            break;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Allow login using either the email address or the username
    $stmt = $pdo->prepare("SELECT * FROM users WHERE (email = :identifier OR username = :identifier) AND is_verified = 1");
    $stmt->execute(['identifier' => $identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] !== 'active') {
            // Account has been deactivated by an administrator
            $error = "This account has been deactivated. Please contact an administrator.";
            logActivity($pdo, $user['id'], $user['email'], 'login', 'failed');
        } else {
            // Successful login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Log successful login
            logActivity($pdo, $user['id'], $user['email'], 'login', 'success');

            switch($user['role']) {
                case 'admin':
                    redirect('/app/admin/dashboard.php');
                    break;
                case 'manager':
                    redirect('/app/manager/dashboard.php');
                    break;
                case 'user':
                    redirect('/app/user/dashboard.php');
                    break;
            }
        }
    } else {
        // Failed login
        $error = "Invalid credentials or email not verified";

        // Log failed login attempt
        logActivity($pdo, null, $identifier, 'login', 'failed');
    }
}

renderHeader('Login');
?>

<h1>Login</h1>

<?php if ($error): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="POST">
    <div class="form-group">
        <label for="email">Username or Email:</label>
        <input type="text" id="email" name="email" required autofocus>
    </div>
    
    <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
    </div>
    
    <button type="submit">Login</button>
</form>


<?php renderFooter(); ?>