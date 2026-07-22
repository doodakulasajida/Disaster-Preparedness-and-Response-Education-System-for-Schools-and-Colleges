<?php
session_start();
include("../config/db.php");

// Brute force protection
$max_attempts = 5;
$lockout_time = 900; // 15 minutes in seconds

if (!isset($_SESSION['admin_login_attempts'])) {
    $_SESSION['admin_login_attempts'] = 0;
    $_SESSION['admin_last_attempt'] = time();
}

// Check if locked out
if ($_SESSION['admin_login_attempts'] >= $max_attempts && 
    (time() - $_SESSION['admin_last_attempt']) < $lockout_time) {
    $remaining_time = $lockout_time - (time() - $_SESSION['admin_last_attempt']);
    $error = "Too many failed attempts. Please try again in " . ceil($remaining_time / 60) . " minutes.";
    $locked = true;
} else {
    $locked = false;
    if (time() - $_SESSION['admin_last_attempt'] >= $lockout_time) {
        // Reset attempts after lockout period
        $_SESSION['admin_login_attempts'] = 0;
    }
}

$error = '';

if (isset($_POST['login']) && !$locked) {
    // Sanitize inputs
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    // Use prepared statement for security
    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND password=? AND role='admin'");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        // Successful login
        $user = $res->fetch_assoc();
        $_SESSION['admin'] = true;
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['admin_name'] = $user['name'];
        $_SESSION['admin_login_time'] = time();
        
        // Reset login attempts
        $_SESSION['admin_login_attempts'] = 0;
        
        // Log admin login (optional)
        $admin_id = $user['id'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $conn->query("INSERT INTO admin_logs (admin_id, action, ip_address) VALUES ('$admin_id', 'login', '$ip')");
        
        header("Location: dashboard.php");
        exit();
    } else {
        // Failed login
        $_SESSION['admin_login_attempts']++;
        $_SESSION['admin_last_attempt'] = time();
        $remaining_attempts = $max_attempts - $_SESSION['admin_login_attempts'];
        $error = "Invalid admin credentials! " . $remaining_attempts . " attempts remaining.";
    }
    $stmt->close();
}

// Get client IP for display (optional)
$client_ip = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Disaster Preparedness System</title>
    <link rel="stylesheet" href="css/login.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-header">
            <div class="admin-badge">ADMIN PANEL</div>
            <div class="admin-icon">🛡️</div>
            <h2>Administrator Access</h2>
            <p>Secure Login for Disaster Management System</p>
        </div>
        
        <div class="admin-form">
            <?php if ($error): ?>
                <div class="error-message" id="errorMessage">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                    <i class="fas fa-times close-btn" onclick="this.parentElement.style.display='none'"></i>
                </div>
            <?php endif; ?>
            
            <?php if (isset($locked) && $locked): ?>
                <div class="error-message" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);">
                    <i class="fas fa-lock"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="adminLoginForm">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Admin Email
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-user-shield"></i>
                        <input type="email" id="email" name="email" placeholder="admin@example.com" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               autocomplete="off">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Secure Password
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-key"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <i class="fas fa-eye password-toggle" onclick="togglePassword()"></i>
                    </div>
                </div>
                
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember this device</label>
                </div>
                
                <button type="submit" name="login" class="admin-login-btn" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i> Access Admin Panel
                </button>
            </form>
            
            <div class="security-badge">
                <i class="fas fa-shield-alt"></i>
                <span>Secure Connection | 256-bit SSL Encrypted</span>
                <i class="fas fa-lock"></i>
            </div>
            
            <div class="admin-links">
                <a href="forgot_password.php">
                    <i class="fas fa-question-circle"></i> Forgot Admin Password?
                </a>
                <br><br>
                <a href="../index.php">
                    <i class="fas fa-arrow-left"></i> Back to Main Site
                </a>
            </div>
            
            <div class="ip-info">
                <i class="fas fa-network-wired"></i> 
                Your IP: <?php echo htmlspecialchars($client_ip); ?> 
                <span class="tooltip" onclick="copyToClipboard('<?php echo $client_ip; ?>')">
                    <i class="fas fa-copy"></i>
                </span>
            </div>
            
            <div class="attempt-indicator">
                <i class="fas fa-chart-line"></i>
                Failed attempts: <?php echo $_SESSION['admin_login_attempts']; ?> / <?php echo $max_attempts; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Password visibility toggle
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Form validation with security checks (password length restriction removed)
        document.getElementById('adminLoginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const submitBtn = document.getElementById('loginBtn');
            
            // Basic validation
            if (!email || !password) {
                e.preventDefault();
                showError('Please enter both email and password');
                return;
            }
            
            // Email format validation
            if (!isValidEmail(email)) {
                e.preventDefault();
                showError('Please enter a valid email address');
                return;
            }
            
            // Password length check - REMOVED
            // No password length restriction
            
            // Show loading state
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authenticating...';
        });
        
        // Email validation
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
        
        // Show error message
        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.innerHTML = `
                <i class="fas fa-exclamation-circle"></i>
                <span>${message}</span>
                <i class="fas fa-times close-btn" onclick="this.parentElement.style.display='none'"></i>
            `;
            
            const form = document.getElementById('adminLoginForm');
            form.parentNode.insertBefore(errorDiv, form);
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                if (errorDiv) errorDiv.remove();
            }, 5000);
        }
        
        // Copy to clipboard function
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text);
            showNotification('IP copied to clipboard');
        }
        
        // Show notification
        function showNotification(message) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: #27ae60;
                color: white;
                padding: 10px 20px;
                border-radius: 5px;
                z-index: 1000;
                animation: slideIn 0.3s ease;
            `;
            notification.innerHTML = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
        
        // Remember me functionality
        if (localStorage.getItem('rememberedAdminEmail')) {
            document.getElementById('email').value = localStorage.getItem('rememberedAdminEmail');
            document.getElementById('remember').checked = true;
        }
        
        document.getElementById('adminLoginForm').addEventListener('submit', function() {
            if (document.getElementById('remember').checked) {
                localStorage.setItem('rememberedAdminEmail', document.getElementById('email').value);
            } else {
                localStorage.removeItem('rememberedAdminEmail');
            }
        });
        
        // Auto-hide error message after 5 seconds
        setTimeout(() => {
            const errorMsg = document.getElementById('errorMessage');
            if (errorMsg) {
                errorMsg.style.opacity = '0';
                setTimeout(() => {
                    if (errorMsg) errorMsg.remove();
                }, 300);
            }
        }, 5000);
        
        // Prevent right-click on admin page (optional security)
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });
        
        // Keyboard shortcut: Ctrl + Shift + L for login
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.key === 'L') {
                e.preventDefault();
                document.getElementById('loginBtn').click();
            }
        });
        
        // Log failed attempts to console (for monitoring)
        const failedAttempts = <?php echo $_SESSION['admin_login_attempts']; ?>;
        if (failedAttempts > 0) {
            console.warn(`⚠️ Admin login: ${failedAttempts} failed attempts detected`);
        }
        
        // Add animation to admin icon
        // const adminIcon = document.querySelector('.admin-icon');
        // setInterval(() => {
        //     adminIcon.style.transform = 'scale(1.1)';
        //     setTimeout(() => {
        //         adminIcon.style.transform = 'scale(1)';
        //     }, 300);
        // }, 3000);
    </script>
</body>
</html>