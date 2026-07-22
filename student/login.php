<?php
session_start();
include("config/db.php");

$error = "";

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    // Debug: Log the attempted login
    error_log("Login attempt - Email: " . $email);
    
    // Use prepared statement for security
    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $user = $res->fetch_assoc();
        
        // Debug: Show user found
        error_log("User found: " . print_r($user, true));
        
        // Check password (exact match since you're storing plain text)
        if ($password == $user['password']) {
            // Password matches
            $_SESSION['user'] = $user;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            error_log("Login successful for: " . $email . " Role: " . $user['role']);
            
            // Redirect based on role
            if ($user['role'] == 'admin') {
                $_SESSION['admin'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_name'] = $user['name'];
                $_SESSION['admin_email'] = $user['email'];
                
                header("Location: admin/dashboard.php");
                exit();
            } else {
                header("Location: student/dashboard.php");
                exit();
            }
        } else {
            // Password doesn't match
            error_log("Password mismatch for: " . $email . " - Entered: " . $password . " - DB: " . $user['password']);
            $error = "Invalid email or password! (Password mismatch)";
        }
    } else {
        // User not found
        error_log("User not found: " . $email);
        $error = "Invalid email or password! (User not found)";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Disaster Preparedness System</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-icon">🛡️</div>
            <h2>Welcome Back!</h2>
            <p>Login to access your disaster preparedness training</p>
        </div>
        
        <div class="login-form">
            <?php if ($error): ?>
                <div class="error-message" id="errorMessage">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                    <i class="fas fa-times close-btn" onclick="this.parentElement.style.display='none'"></i>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-key"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <i class="fas fa-eye password-toggle" onclick="togglePassword()"></i>
                    </div>
                </div>
                
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>
                
                <button type="submit" name="login" class="login-btn" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="register-link">
                <p>Don't have an account? <a href="student/register.php"><i class="fas fa-user-plus"></i> Register here</a></p>
            </div>
            
            <!-- Demo Credentials - Updated with your database values -->
            <div class="demo-credentials">
                <p><i class="fas fa-info-circle"></i> Demo Credentials:</p>
                <p><strong>Student:</strong> <span class="cred" onclick="fillDemo('mdnooh49@gmail.com', '12345678')">mdnooh49@gmail.com / 12345678</span></p>
                <p><strong>Admin:</strong> <span class="cred" onclick="fillDemo('admin@gmail.com', 'admin')">admin@gmail.com / admin</span></p>
                <p><strong>Student 2:</strong> <span class="cred" onclick="fillDemo('thummalabhargavi7330@gmail.com', 'Bubu@1234')">thummalabhargavi7330@gmail.com / Bubu@1234</span></p>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
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
        
        // Fill demo credentials
        function fillDemo(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
            
            // Highlight the fields
            const emailField = document.getElementById('email');
            const passwordField = document.getElementById('password');
            
            emailField.style.borderColor = '#4caf50';
            passwordField.style.borderColor = '#4caf50';
            
            setTimeout(() => {
                emailField.style.borderColor = '';
                passwordField.style.borderColor = '';
            }, 2000);
        }
        
        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const submitBtn = document.getElementById('loginBtn');
            
            if (!email || !password) {
                e.preventDefault();
                showError('Please enter both email and password');
                return false;
            }
            
            if (!isValidEmail(email)) {
                e.preventDefault();
                showError('Please enter a valid email address');
                return false;
            }
            
            // Show loading state
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
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
            
            const form = document.getElementById('loginForm');
            const existingError = form.parentNode.querySelector('.error-message:not(#errorMessage)');
            if (existingError) {
                existingError.remove();
            }
            form.parentNode.insertBefore(errorDiv, form);
            
            setTimeout(() => {
                if (errorDiv) errorDiv.remove();
            }, 5000);
        }
        
        // Remember me functionality
        if (localStorage.getItem('rememberedEmail')) {
            document.getElementById('email').value = localStorage.getItem('rememberedEmail');
            document.getElementById('remember').checked = true;
        }
        
        document.getElementById('loginForm').addEventListener('submit', function() {
            if (document.getElementById('remember').checked) {
                localStorage.setItem('rememberedEmail', document.getElementById('email').value);
            } else {
                localStorage.removeItem('rememberedEmail');
            }
        });
        
        // Auto-hide error message after 5 seconds
        setTimeout(() => {
            const errorMsg = document.getElementById('errorMessage');
            if (errorMsg) {
                setTimeout(() => {
                    errorMsg.style.opacity = '0';
                    setTimeout(() => {
                        if (errorMsg) errorMsg.style.display = 'none';
                    }, 300);
                }, 5000);
            }
        }, 1000);
        
        // Focus on email field
        document.getElementById('email').focus();
        
        // Add animation to login icon
        const loginIcon = document.querySelector('.login-icon');
        setInterval(() => {
            loginIcon.style.transform = 'scale(1.1)';
            setTimeout(() => {
                loginIcon.style.transform = 'scale(1)';
            }, 300);
        }, 3000);
        
        // Keyboard shortcut: Enter to submit
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const submitBtn = document.getElementById('loginBtn');
                if (submitBtn && !submitBtn.disabled) {
                    submitBtn.click();
                }
            }
        });
    </script>
</body>
</html>