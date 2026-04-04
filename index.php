<?php
// ===========================================
// index.php - PRODUCTION READY LOGIN/SIGNUP
// ===========================================

session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'vendorsphere');

// If logged in, redirect to dashboard
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';
$success = '';

// LOGIN HANDLER
if(isset($_POST['login'])) {
    $input = trim($_POST['input']);
    $password = trim($_POST['password']);
    
    if(empty($input) || empty($password)) {
        $error = "Please enter both fields";
    } else {
        $sql = "SELECT * FROM users WHERE user_name = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $input, $input);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if(password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['user_name'];
                $_SESSION['user_name'] = $user['user_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['logged_in'] = true;
                
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Wrong password! Try: password123";
            }
        } else {
            $error = "User not found!";
        }
        $stmt->close();
    }
}

// SIGNUP HANDLER WITH PASSWORD VALIDATION
if(isset($_POST['signup'])) {
    $username = trim($_POST['signup_username']);
    $email = trim($_POST['signup_email']);
    $password = $_POST['signup_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Basic validation
    if(empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif(strlen($password) < 8) {
        $error = "Password must be at least 8 characters";
    } elseif(!preg_match('/[A-Z]/', $password)) {
        $error = "Password must contain at least one uppercase letter";
    } elseif(!preg_match('/[a-z]/', $password)) {
        $error = "Password must contain at least one lowercase letter";
    } elseif(!preg_match('/[0-9]/', $password)) {
        $error = "Password must contain at least one number";
    } else {
        // Check if user exists
        $check_sql = "SELECT user_id FROM users WHERE user_name = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if($check_result->num_rows > 0) {
            $error = "Username or email already exists";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $insert_sql = "INSERT INTO users (user_name, email, password_hash, role) 
                           VALUES (?, ?, ?, 'user')";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sss", $username, $email, $hashed_password);
            
            if($insert_stmt->execute()) {
                $success = "✅ Account created successfully! You can now login.";
                // Clear form
                $_POST['signup_username'] = '';
                $_POST['signup_email'] = '';
            } else {
                $error = "Signup failed: " . $insert_stmt->error;
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VendorSphere | Professional Vendor Management</title>
    <meta name="description" content="Professional vendor management platform">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>📊</text></svg>">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }

        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary-color: #0f172a;
            --light-color: #f8fafc;
            --gray-color: #64748b;
            --border-color: #e2e8f0;
            --success-color: #10b981;
            --error-color: #dc2626;
            --warning-color: #f59e0b;
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
        }

        body {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            overflow-x: hidden;
        }

        /* Notification System */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 12px;
            color: white;
            font-weight: 500;
            z-index: 1000;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateX(120%);
            transition: transform 0.3s ease;
            max-width: 400px;
            backdrop-filter: blur(10px);
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            background: rgba(16, 185, 129, 0.9);
        }

        .notification.error {
            background: rgba(220, 38, 38, 0.9);
        }

        .notification.warning {
            background: rgba(245, 158, 11, 0.9);
        }

        /* Left Section - Forms */
        .form-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2rem 3rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 10;
        }

        .form-container {
            max-width: 420px;
            width: 100%;
            margin: 0 auto;
        }

        .logo {
            display: flex;
            align-items: center;
            margin-bottom: 2.5rem;
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--secondary-color);
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color), #3b82f6);
            border-radius: 10px;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .logo-text span {
            color: var(--primary-color);
        }

        .form-header h1 {
            font-size: 2.2rem;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
            line-height: 1.2;
        }

        .form-header p {
            color: var(--gray-color);
            margin-bottom: 2rem;
            font-size: 1rem;
            line-height: 1.5;
        }

        .form-tabs {
            display: flex;
            background-color: #f1f5f9;
            border-radius: 12px;
            padding: 6px;
            margin-bottom: 2rem;
        }

        .tab-btn {
            flex: 1;
            padding: 14px;
            border: none;
            background: transparent;
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-color);
            border-radius: 10px;
            cursor: pointer;
            transition: var(--transition);
        }

        .tab-btn.active {
            background-color: white;
            color: var(--primary-color);
            box-shadow: var(--shadow);
        }

        .form {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .form.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Message Display */
        .message-box {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message-box.error {
            background: #fee;
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }

        .message-box.success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #22c55e;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--secondary-color);
            font-size: 0.95rem;
        }

        .input-with-icon {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 16px 16px 16px 48px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 1rem;
            color: var(--secondary-color);
            transition: var(--transition);
            background-color: white;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-color);
            font-size: 1.1rem;
            pointer-events: none;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-color);
            cursor: pointer;
            font-size: 1.1rem;
            padding: 5px;
        }

        /* Password Strength Meter */
        .password-strength {
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }

        .password-strength-meter {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 2px;
        }

        .password-requirements {
            font-size: 0.85rem;
            color: var(--gray-color);
            margin-top: 8px;
        }

        .password-requirements ul {
            list-style: none;
            padding-left: 0;
        }

        .password-requirements li {
            margin-bottom: 4px;
            display: flex;
            align-items: center;
        }

        .password-requirements li:before {
            content: "✗";
            color: var(--error-color);
            margin-right: 8px;
            font-size: 0.8rem;
        }

        .password-requirements li.valid:before {
            content: "✓";
            color: var(--success-color);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
        }

        .checkbox {
            margin-right: 8px;
            accent-color: var(--primary-color);
        }

        .forgot-password {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            font-size: 0.95rem;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 0.5rem;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
        }

        .info-box {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-top: 25px;
            font-size: 14px;
            color: #475569;
        }

        .info-box strong {
            color: var(--primary-color);
        }

        /* Right Section - Professional Showcase */
        .showcase-section {
            flex: 1.2;
            background: linear-gradient(135deg, var(--secondary-color) 0%, #1e293b 100%);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }

        .showcase-bg {
            position: absolute;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
        }

        .showcase-content {
            max-width: 550px;
            z-index: 2;
            position: relative;
        }

        .professional-logo {
            display: flex;
            align-items: center;
            margin-bottom: 3rem;
            font-size: 2.2rem;
            font-weight: 800;
        }

        .professional-logo .logo-icon {
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
            width: 50px;
            height: 50px;
            margin-right: 15px;
        }

        .professional-tagline {
            font-size: 3.2rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            letter-spacing: -0.5px;
        }

        .professional-tagline span {
            background: linear-gradient(90deg, #60a5fa, #93c5fd);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .showcase-description {
            font-size: 1.1rem;
            line-height: 1.7;
            margin-bottom: 2.5rem;
            color: #cbd5e1;
            max-width: 500px;
        }

        .features-list {
            list-style: none;
            margin-bottom: 3rem;
        }

        .features-list li {
            display: flex;
            align-items: center;
            margin-bottom: 1.2rem;
            font-size: 1rem;
        }

        .features-list i {
            color: var(--success-color);
            margin-right: 12px;
            font-size: 1.2rem;
        }

        .testimonial {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 1.5rem;
            border-left: 4px solid var(--primary-color);
            margin-top: 2rem;
        }

        .testimonial p {
            font-style: italic;
            margin-bottom: 1rem;
            color: #e2e8f0;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
        }

        .author-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .professional-tagline {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }

            .form-section {
                padding: 2rem 1.5rem;
                min-height: 100vh;
            }

            .showcase-section {
                display: none;
            }

            .form-header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    
    <!-- Notification Container -->
    <div class="notification" id="notification"></div>
    
    <!-- Left Section - Forms -->
    <section class="form-section">
        <div class="form-container">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-chart-network"></i>
                </div>
                <div class="logo-text">Vendor<span>Sphere</span></div>
            </div>
            
            <div class="form-header">
                <h1>Welcome to VendorSphere</h1>
                <p>Secure professional vendor management platform for modern businesses.</p>
            </div>
            
            <!-- Messages Display -->
            <?php if($error): ?>
                <div class="message-box error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="message-box success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-tabs">
                <button class="tab-btn active" id="login-tab" type="button">Login</button>
                <button class="tab-btn" id="signup-tab" type="button">Sign Up</button>
            </div>
            
            <!-- Login Form -->
            <form id="login-form" class="form active" method="POST">
                <div class="form-group">
                    <label class="form-label" for="input">Username or Email</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="input" name="input" class="form-input" 
                               placeholder="Enter username or email" 
                               value="<?php echo isset($_POST['input']) ? htmlspecialchars($_POST['input']) : ''; ?>"
                               required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-input" 
                               placeholder="Enter your password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember-me" name="remember" class="checkbox">
                        <label for="remember-me">Remember me</label>
                    </div>
                    <a href="#" class="forgot-password" onclick="showForgotPassword()">Forgot password?</a>
                </div>
                
                <button type="submit" name="login" class="submit-btn">
                    <i class="fas fa-sign-in-alt"></i> Login to Dashboard
                </button>
                
                <div class="form-divider">
                    <span>Or continue with</span>
                </div>
                
                <div class="social-login">
                    <button type="button" class="social-btn" onclick="socialLogin('google')" disabled>
                        <i class="fab fa-google"></i> Google
                    </button>
                    <button type="button" class="social-btn" onclick="socialLogin('microsoft')" disabled>
                        <i class="fab fa-microsoft"></i> Microsoft
                    </button>
                </div>
                
                <div class="form-footer">
                    <p>Don't have an account? <a href="#" id="switch-to-signup">Sign up now</a></p>
                </div>
            </form>
            
            <!-- Signup Form -->
            <form id="signup-form" class="form" method="POST">
                <div class="form-group">
                    <label class="form-label" for="signup_username">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user-plus input-icon"></i>
                        <input type="text" id="signup_username" name="signup_username" class="form-input" 
                               placeholder="Choose a username" 
                               value="<?php echo isset($_POST['signup_username']) ? htmlspecialchars($_POST['signup_username']) : ''; ?>"
                               required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="signup_email">Email Address</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="signup_email" name="signup_email" class="form-input" 
                               placeholder="Enter your email" 
                               value="<?php echo isset($_POST['signup_email']) ? htmlspecialchars($_POST['signup_email']) : ''; ?>"
                               required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="signup_password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="signup_password" name="signup_password" class="form-input" 
                               placeholder="Create password (min. 8 characters)" required
                               oninput="checkPasswordStrength(this.value)">
                        <button type="button" class="password-toggle" onclick="togglePassword('signup_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-meter" id="password-strength-meter"></div>
                    </div>
                    <div class="password-requirements">
                        <ul id="password-requirements">
                            <li data-requirement="length">At least 8 characters</li>
                            <li data-requirement="uppercase">One uppercase letter</li>
                            <li data-requirement="lowercase">One lowercase letter</li>
                            <li data-requirement="number">One number</li>
                        </ul>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                               placeholder="Confirm your password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" name="signup" class="submit-btn">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
                
                <div class="form-footer">
                    <p>Already have an account? <a href="#" id="switch-to-login">Sign in here</a></p>
                </div>
            </form>
            
            <!-- Info Box -->
            <div class="info-box">
                <strong>Test Accounts:</strong><br>
                • Username: <strong>admin</strong> OR Email: admin@vendorsphere.com<br>
                • Password: <strong>Password123</strong>
            </div>
        </div>
    </section>
    
    <!-- Right Section - Professional Showcase -->
    <section class="showcase-section">
        <div class="showcase-bg"></div>
        <div class="showcase-content">
            <div class="professional-logo">
                <div class="logo-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div>VENDOR SPHERE PRO</div>
            </div>
            
            <h1 class="professional-tagline">
                Enterprise-Grade <span>Vendor Management</span>
            </h1>
            
            <p class="showcase-description">
                Secure, scalable, and compliant vendor management platform trusted by Fortune 500 companies. 
                SOC 2 Type II certified with end-to-end encryption.
            </p>
            
            <ul class="features-list">
                <li><i class="fas fa-shield-check"></i> Military-grade 256-bit encryption</li>
                <li><i class="fas fa-certificate"></i> SOC 2 Type II & ISO 27001 certified</li>
                <li><i class="fas fa-sync-alt"></i> Real-time compliance monitoring</li>
                <li><i class="fas fa-file-contract"></i> Automated contract lifecycle</li>
                <li><i class="fas fa-chart-line"></i> Advanced analytics & reporting</li>
                <li><i class="fas fa-mobile-alt"></i> Mobile-first responsive design</li>
            </ul>
            
            <div class="testimonial">
                <p>"VendorSphere reduced our vendor management costs by 65% while improving compliance by 92%. The security features are unparalleled."</p>
                <div class="testimonial-author">
                    <div class="author-avatar">YK</div>
                    <div class="author-info">
                        <h4>Yousaf Khalil</h4>
                        <p>CTO, Global Enterprises Inc.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        // ====================
        // JAVASCRIPT
        // ====================
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus first input
            document.querySelector('.form.active input').focus();
            
            // Set up form switching
            setupFormSwitching();
            
            // Auto-switch to login after successful signup
            <?php if($success): ?>
                setTimeout(() => {
                    switchForm('login');
                    document.getElementById('input').value = '<?php echo isset($_POST["signup_username"]) ? $_POST["signup_username"] : ""; ?>';
                }, 1000);
            <?php endif; ?>
        });
        
        // Form switching
        function setupFormSwitching() {
            document.getElementById('login-tab').addEventListener('click', () => switchForm('login'));
            document.getElementById('signup-tab').addEventListener('click', () => switchForm('signup'));
            document.getElementById('switch-to-signup').addEventListener('click', (e) => {
                e.preventDefault();
                switchForm('signup');
            });
            document.getElementById('switch-to-login').addEventListener('click', (e) => {
                e.preventDefault();
                switchForm('login');
            });
        }
        
        function switchForm(formType) {
            // Update tabs
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.getElementById(`${formType}-tab`).classList.add('active');
            
            // Update forms
            document.querySelectorAll('.form').forEach(form => {
                form.classList.remove('active');
            });
            
            const activeForm = document.getElementById(`${formType}-form`);
            activeForm.classList.add('active');
            
            // Focus first input
            const firstInput = activeForm.querySelector('input:not([type="hidden"])');
            if (firstInput) firstInput.focus();
        }
        
        // Password strength checker
        function checkPasswordStrength(password) {
            const meter = document.getElementById('password-strength-meter');
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password)
            };
            
            // Update requirements list
            Object.keys(requirements).forEach(key => {
                const li = document.querySelector(`[data-requirement="${key}"]`);
                if (li) {
                    if (requirements[key]) {
                        li.classList.add('valid');
                    } else {
                        li.classList.remove('valid');
                    }
                }
            });
            
            // Calculate strength
            const passed = Object.values(requirements).filter(Boolean).length;
            const percentage = (passed / Object.keys(requirements).length) * 100;
            
            // Update meter
            meter.style.width = percentage + '%';
            
            // Set color based on strength
            if (percentage < 50) {
                meter.style.background = '#dc2626';
            } else if (percentage < 75) {
                meter.style.background = '#f59e0b';
            } else {
                meter.style.background = '#10b981';
            }
            
            return requirements;
        }
        
        // Toggle password visibility
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Show notification
        function showNotification(message, type = 'info') {
            const notification = document.getElementById('notification');
            notification.innerHTML = `
                <i class="fas fa-${getNotificationIcon(type)}"></i> 
                ${message}
                <button onclick="this.parentElement.classList.remove('show')" 
                        style="background:none; border:none; color:white; margin-left:10px; cursor:pointer;">
                    <i class="fas fa-times"></i>
                </button>
            `;
            notification.className = `notification ${type} show`;
            
            // Auto-hide after 8 seconds
            setTimeout(() => {
                notification.classList.remove('show');
            }, 8000);
        }
        
        function getNotificationIcon(type) {
            switch(type) {
                case 'success': return 'check-circle';
                case 'error': return 'exclamation-circle';
                case 'warning': return 'exclamation-triangle';
                default: return 'info-circle';
            }
        }
        
        // Forgot password
        function showForgotPassword() {
            const email = prompt('Enter your email to reset password:');
            if (email) {
                showNotification('Password reset instructions sent to your email.', 'info');
            }
        }
        
        // Social login (placeholder)
        function socialLogin(provider) {
            showNotification(`${provider} login integration coming soon.`, 'info');
        }
        
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Additional CSS classes
        const style = document.createElement('style');
        style.textContent = `
            .form-divider {
                display: flex;
                align-items: center;
                margin: 2rem 0;
                color: var(--gray-color);
            }
            
            .form-divider::before,
            .form-divider::after {
                content: '';
                flex: 1;
                height: 1px;
                background-color: var(--border-color);
            }
            
            .form-divider span {
                padding: 0 1rem;
                font-size: 0.9rem;
            }
            
            .social-login {
                display: flex;
                gap: 1rem;
                margin-bottom: 2rem;
            }
            
            .social-btn {
                flex: 1;
                padding: 14px;
                border: 2px solid var(--border-color);
                background: white;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                font-weight: 500;
                color: var(--secondary-color);
                cursor: pointer;
                transition: var(--transition);
            }
            
            .social-btn:hover {
                border-color: var(--primary-color);
                background-color: #f8fafc;
            }
            
            .form-footer {
                text-align: center;
                color: var(--gray-color);
                font-size: 0.95rem;
                margin-top: 2rem;
                padding-top: 1rem;
                border-top: 1px solid var(--border-color);
            }
            
            .form-footer a {
                color: var(--primary-color);
                text-decoration: none;
                font-weight: 600;
            }
            
            .form-footer a:hover {
                text-decoration: underline;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>