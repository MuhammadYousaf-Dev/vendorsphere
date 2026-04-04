<?php
// ===========================================
// dashboard.php - FIXED VERSION WITH ALL LINKS
// ===========================================

session_start();
ob_start();

// SIMPLE SESSION CHECK - NO REDIRECT LOOP
if (!isset($_SESSION['user_id'])) {
    // Try alternative session variable names
    if (isset($_SESSION['username']) || isset($_SESSION['user_name'])) {
        // User has some session, allow access
    } else {
        header('Location: index.php');
        exit;
    }
}

// Ensure all required session variables
if (!isset($_SESSION['username']) && isset($_SESSION['user_name'])) {
    $_SESSION['username'] = $_SESSION['user_name'];
}

if (!isset($_SESSION['user_role']) && isset($_SESSION['role'])) {
    $_SESSION['user_role'] = $_SESSION['role'];
}

if (!isset($_SESSION['user_email'])) {
    $_SESSION['user_email'] = $_SESSION['email'] ?? 'user@vendorsphere.com';
}

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// Database config
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'vendorsphere');

// Database connection
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $conn->set_charset("utf8mb4");
        } catch (mysqli_sql_exception $e) {
            error_log("Dashboard DB connection failed: " . $e->getMessage());
            return null;
        }
    }
    return $conn;
}

// Get user stats
function getUserStats($user_id) {
    $stats = [
        'total_vendors' => rand(5, 50),
        'active_contracts' => rand(1, 20),
        'pending_tasks' => rand(0, 10),
    ];
    
    return $stats;
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Get user stats
$user_stats = getUserStats($_SESSION['user_id'] ?? 0);

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | VendorSphere</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --accent: #4cc9f0;
            --success: #4ade80;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #2b2d42;
            --light: #f8f9fa;
            --sidebar-width: 260px;
            --header-height: 70px;
            --transition: all 0.3s ease;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px 0;
            position: fixed;
            height: 100vh;
            box-shadow: 5px 0 25px rgba(0, 0, 0, 0.1);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 100;
            display: flex;
            flex-direction: column;
            overflow: hidden; 
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 0 20px 20px 20px; /* CHANGE: Add bottom padding */
            border-bottom: 1px solid rgba(0, 0, 0, 0.05); /* Optional divider */
            flex-shrink: 0; /* Prevent header from shrinking */
        }

        .sidebar-logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            margin: 0 auto 15px;
        }

        .sidebar-logo-text {
            color: var(--primary);
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-logo-text span {
            color: var(--accent);
            font-size: 12px;
            font-weight: 300;
            letter-spacing: 2px;
        }

        .sidebar-nav {
            padding: 0 15px 25px 15px;
            flex: 1;
             /* max-height: calc(100vh - 150px); */
            overflow-y: auto;
            overflow-x: hidden; 
        }

        .nav-section {
            margin-bottom: 25px;
        }

        .nav-section-title {
            color: var(--dark);
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            padding: 0 20px;
            opacity: 0.7;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: var(--dark);
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-item i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
            color: var(--primary);
        }

        .nav-item:hover, .nav-item.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            transform: translateX(10px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .nav-item:hover i {
            color: white;
        }

        .badge {
            margin-left: auto;
            background: var(--primary);
            color: white;
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 20px;
            min-width: 25px;
            text-align: center;
        }

        .badge.coming-soon-badge {
            background: #6b7280 !important;
            font-size: 10px;
        }

        /* Coming Soon Items */
        .nav-item.coming-soon {
            opacity: 0.7;
            cursor: not-allowed;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        /* Header */
        .header {
            height: var(--header-height);
            background: white;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 90;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--dark);
            cursor: pointer;
            background: var(--light);
            width: 40px;
            height: 40px;
            border-radius: 10px;
        }

        .search-bar {
            position: relative;
            width: 300px;
        }

        .search-bar input {
            width: 100%;
            padding: 12px 16px 12px 45px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 14px;
            transition: var(--transition);
            background: var(--light);
        }

        .search-bar input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .search-bar i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--dark);
            opacity: 0.5;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-icon {
            background: none;
            border: none;
            font-size: 1.2rem;
            color: var(--dark);
            cursor: pointer;
            position: relative;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .header-icon:hover {
            background: var(--light);
        }

        .header-icon .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 10px;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px;
            border-radius: 12px;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            background: var(--light);
        }

        .user-menu:hover {
            background: #eef2ff;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--dark);
        }

        .user-role {
            font-size: 0.8rem;
            color: var(--dark);
            opacity: 0.7;
            background: white;
            padding: 2px 8px;
            border-radius: 12px;
            display: inline-block;
        }

        .user-menu i {
            color: var(--dark);
            opacity: 0.7;
        }

        /* User Dropdown */
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 220px;
            z-index: 1000;
            border: 1px solid #e5e7eb;
            display: none;
            overflow: hidden;
        }

        .user-dropdown.active {
            display: block;
        }

        .user-dropdown-header {
            padding: 20px;
            border-bottom: 1px solid #f3f4f6;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .user-dropdown-header div:first-child {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .user-dropdown-header div:last-child {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .user-dropdown-item {
            display: flex;
            align-items: center;
            padding: 15px;
            text-decoration: none;
            color: var(--dark);
            border-bottom: 1px solid #f3f4f6;
            transition: var(--transition);
        }

        .user-dropdown-item:hover {
            background: #f8fafc;
            color: var(--primary);
        }

        .user-dropdown-item i {
            width: 20px;
            margin-right: 10px;
            color: var(--dark);
            opacity: 0.7;
        }

        .user-dropdown-item.logout {
            color: var(--danger);
        }

        .user-dropdown-item.logout i {
            color: var(--danger);
        }

        /* Dashboard Content */
        .dashboard-content {
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(67, 97, 238, 0.3);
        }

        .welcome-banner:before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        }

        .welcome-banner h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .welcome-banner p {
            font-size: 16px;
            opacity: 0.9;
            max-width: 600px;
        }

        .welcome-date {
            margin-top: 15px;
            font-size: 14px;
            opacity: 0.8;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-icon.vendors { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; }
        .stat-icon.contracts { background: linear-gradient(135deg, var(--success), #10b981); color: white; }
        .stat-icon.tasks { background: linear-gradient(135deg, var(--warning), #d97706); color: white; }
        .stat-icon.revenue { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            font-weight: 600;
        }

        .stat-trend.positive { color: var(--success); }
        .stat-trend.negative { color: var(--danger); }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--dark);
        }

        .stat-label {
            color: var(--dark);
            opacity: 0.7;
            font-size: 14px;
        }

        /* Quick Actions */
        .quick-actions {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .section-title {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 25px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary);
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 25px 20px;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 15px;
            text-decoration: none;
            color: var(--dark);
            transition: var(--transition);
            text-align: center;
            cursor: pointer;
        }

        .action-btn:hover {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-color: transparent;
            transform: translateY(-5px);
            color: white;
            box-shadow: 0 10px 25px rgba(67, 97, 238, 0.3);
        }

        .action-btn:hover i {
            color: white;
        }

        .action-btn i {
            font-size: 30px;
            margin-bottom: 15px;
            color: var(--primary);
        }

        .action-btn span {
            font-weight: 500;
            font-size: 14px;
        }

        /* Recent Activity */
        .recent-activity {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .activity-list {
            list-style: none;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 20px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            flex-shrink: 0;
            font-size: 20px;
        }

        .activity-icon.success { background: #dcfce7; color: var(--success); }
        .activity-icon.info { background: #dbeafe; color: var(--primary); }
        .activity-icon.warning { background: #fef3c7; color: var(--warning); }

        .activity-content {
            flex: 1;
        }

        .activity-text {
            margin-bottom: 8px;
            line-height: 1.5;
            color: var(--dark);
            font-size: 15px;
        }

        .activity-time {
            font-size: 13px;
            color: var(--dark);
            opacity: 0.6;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 20px;
            color: white;
            font-size: 14px;
            margin-top: 30px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .footer p:last-child {
            margin-top: 10px;
            font-size: 12px;
            opacity: 0.8;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .search-bar {
                width: 200px;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 0 15px;
            }
            
            .dashboard-content {
                padding: 20px;
            }
            
            .search-bar {
                display: none;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .user-info {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .actions-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-banner h1 {
                font-size: 24px;
            }
            
            .header-icon {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <span style="font-size: 28px; font-weight: bold; color: white;">VS</span>
                <i class="fas fa-chart-network"></i>
            </div>
            <div class="sidebar-logo-text">VendorSphere</div>
            <div style="color: var(--accent); font-size: 12px; font-weight: 300; letter-spacing: 2px; margin-top: 5px;">VENDOR MANAGEMENT SYSTEM</div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <a href="dashboard.php" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                
                <!-- YOUR EXISTING FILES -->
                <a href="vendors.php" class="nav-item">
                    <i class="fas fa-store"></i>
                    Vendors
                    <span class="badge"><?php echo $user_stats['total_vendors']; ?></span>
                </a>
                
                <a href="contracts.php" class="nav-item">
                    <i class="fas fa-file-contract"></i>
                    Contracts
                    <span class="badge"><?php echo $user_stats['active_contracts']; ?></span>
                </a>
                
                <a href="departments.php" class="nav-item">
                    <i class="fas fa-building"></i>
                    Departments
                    <span class="badge"><?php echo rand(3, 15); ?></span>
                </a>
                
                <a href="purchase_orders.php" class="nav-item">
                    <i class="fas fa-cart-arrow-down"></i>
                    Purchase Orders
                    <span class="badge"><?php echo rand(5, 25); ?></span>
                </a>
                
                <a href="evaluation.php" class="nav-item">
                    <i class="fas fa-star"></i>
                    Evaluation
                    <span class="badge"><?php echo rand(2, 10); ?></span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Coming Soon</div>
                
                <a href="#" class="nav-item coming-soon" onclick="alert('Tasks module coming soon!')">
                    <i class="fas fa-tasks"></i>
                    Tasks
                    <span class="badge coming-soon-badge">Soon</span>
                </a>
                
                <a href="#" class="nav-item coming-soon" onclick="alert('Reports module coming soon!')">
                    <i class="fas fa-chart-bar"></i>
                    Reports
                    <span class="badge coming-soon-badge">Soon</span>
                </a>
                
                <a href="#" class="nav-item coming-soon" onclick="alert('Analytics module coming soon!')">
                    <i class="fas fa-chart-line"></i>
                    Analytics
                    <span class="badge coming-soon-badge">Soon</span>
                </a>
                
                <a href="#" class="nav-item coming-soon" onclick="alert('Compliance module coming soon!')">
                    <i class="fas fa-shield-alt"></i>
                    Compliance
                    <span class="badge coming-soon-badge">Soon</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">System</div>
                
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <a href="users.php" class="nav-item">
                    <i class="fas fa-user-cog"></i>
                    User Management
                </a>
                
                <a href="settings.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
                <?php endif; ?>
                
                <a href="logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search vendors, contracts, tasks...">
                </div>
            </div>
            
            <div class="header-right">
                <button class="header-icon" title="Notifications" onclick="alert('Notifications feature coming soon!')">
                    <i class="fas fa-bell"></i>
                    <span class="badge">3</span>
                </button>
                
                <button class="header-icon" title="Messages" onclick="alert('Messages feature coming soon!')">
                    <i class="fas fa-envelope"></i>
                    <span class="badge">5</span>
                </button>
                
                <!-- User Menu with Dropdown -->
                <div class="user-menu" onclick="toggleUserMenu()">
                    <div class="user-avatar">
                        <?php echo isset($_SESSION['user_name']) ? strtoupper(substr($_SESSION['user_name'], 0, 1)) : 'U'; ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name">
                            <?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User'); ?>
                        </div>
                        <div class="user-role">
                            <?php echo ucfirst($_SESSION['user_role'] ?? $_SESSION['role'] ?? 'User'); ?>
                        </div>
                    </div>
                    <i class="fas fa-chevron-down"></i>
                    
                    <!-- User Dropdown Menu -->
                    <div class="user-dropdown" id="userDropdown">
                        <div class="user-dropdown-header">
                            <div><?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User'); ?></div>
                            <div><?php echo $_SESSION['user_email'] ?? 'user@example.com'; ?></div>
                        </div>
                        <a href="profile.php" class="user-dropdown-item">
                            <i class="fas fa-user"></i>
                            <span>My Profile</span>
                        </a>
                        <a href="settings.php" class="user-dropdown-item">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                        <a href="help.php" class="user-dropdown-item">
                            <i class="fas fa-question-circle"></i>
                            <span>Help & Support</span>
                        </a>
                        <a href="logout.php" class="user-dropdown-item logout">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User'); ?>! 👋</h1>
                <p>Here's what's happening with your vendor management today.</p>
                <div class="welcome-date">
                    <i class="far fa-calendar"></i> <?php echo date('l, F j, Y'); ?>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon vendors">
                            <i class="fas fa-store"></i>
                        </div>
                        <div class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i> 12%
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $user_stats['total_vendors']; ?></div>
                    <div class="stat-label">Total Vendors</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon contracts">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <div class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i> 8%
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $user_stats['active_contracts']; ?></div>
                    <div class="stat-label">Active Contracts</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon tasks">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="stat-trend negative">
                            <i class="fas fa-arrow-down"></i> 3%
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $user_stats['pending_tasks']; ?></div>
                    <div class="stat-label">Pending Tasks</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon revenue">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i> 24%
                        </div>
                    </div>
                    <div class="stat-value">$42.5K</div>
                    <div class="stat-label">Monthly Savings</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2 class="section-title">
                    <i class="fas fa-bolt"></i> Quick Actions
                </h2>
                <div class="actions-grid">
                    <!-- Your existing modules -->
                    <a href="vendors.php?action=add" class="action-btn">
                        <i class="fas fa-plus-circle"></i>
                        <span>Add New Vendor</span>
                    </a>
                    
                    <a href="contracts.php?action=add" class="action-btn">
                        <i class="fas fa-file-contract"></i>
                        <span>Create Contract</span>
                    </a>
                    
                    <a href="departments.php?action=add" class="action-btn">
                        <i class="fas fa-plus-circle"></i>
                        <span>Add Department</span>
                    </a>
                    
                    <a href="purchase_orders.php?action=add" class="action-btn">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Create Purchase Order</span>
                    </a>
                    
                    <a href="evaluation.php?action=add" class="action-btn">
                        <i class="fas fa-star"></i>
                        <span>New Evaluation</span>
                    </a>
                    
                    <!-- Coming soon features -->
                    <a href="#" class="action-btn" onclick="alert('Import feature coming soon!')">
                        <i class="fas fa-file-import"></i>
                        <span>Import Data</span>
                    </a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="recent-activity">
                <h2 class="section-title">
                    <i class="fas fa-history"></i> Recent Activity
                </h2>
                <ul class="activity-list">
                    <li class="activity-item">
                        <div class="activity-icon success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">
                                Contract #CT-2024-0012 with <strong>TechCorp Solutions</strong> was renewed successfully.
                            </div>
                            <div class="activity-time">2 hours ago</div>
                        </div>
                    </li>
                    <li class="activity-item">
                        <div class="activity-icon info">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">
                                New vendor <strong>Global Supplies Inc.</strong> was added to the system.
                            </div>
                            <div class="activity-time">Yesterday, 3:45 PM</div>
                        </div>
                    </li>
                    <li class="activity-item">
                        <div class="activity-icon warning">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">
                                Compliance check failed for <strong>Quality Manufacturing Ltd.</strong> Review required.
                            </div>
                            <div class="activity-time">2 days ago</div>
                        </div>
                    </li>
                    <li class="activity-item">
                        <div class="activity-icon info">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">
                                Monthly vendor performance report for March 2024 was generated.
                            </div>
                            <div class="activity-time">3 days ago</div>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Footer -->
            <footer class="footer">
                <p>© <?php echo date('Y'); ?> VendorSphere. All rights reserved. | v2.1.0</p>
                <p>
                    <i class="fas fa-shield-alt"></i> Secure Connection • 
                    <i class="fas fa-sync-alt"></i> Last updated: <?php echo date('h:i A'); ?>
                </p>
            </footer>
        </div>
    </main>

    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }
        
        // Toggle user dropdown menu
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('active');
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdown');
            const userMenu = document.querySelector('.user-menu');
            
            if (!userMenu.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });
        
        // Search functionality
        const searchInput = document.querySelector('.search-bar input');
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                if (query) {
                    alert('Search feature coming soon! You searched for: ' + query);
                    this.value = '';
                }
            }
        });
        
        // Update current time
        function updateCurrentTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: true 
            });
            
            const dateString = now.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            document.querySelector('.welcome-date').innerHTML = 
                `<i class="far fa-calendar"></i> ${dateString} • ${timeString}`;
        }
        
        // Update time every minute
        updateCurrentTime();
        setInterval(updateCurrentTime, 60000);
        
        // Close sidebar when clicking on mobile links
        if (window.innerWidth <= 1024) {
            document.querySelectorAll('.nav-item').forEach(item => {
                item.addEventListener('click', () => {
                    document.getElementById('sidebar').classList.remove('active');
                });
            });
        }
    </script>
</body>
</html>