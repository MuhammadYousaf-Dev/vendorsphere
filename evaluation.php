<?php
include 'auth.php';
include 'db.php';

$msg = '';
// CHANGE: Use lowercase like purchaseorder and departments
$role_lower = strtolower($_SESSION['role']);
$action = $_GET['action'] ?? '';

// =========================
// ROLE-BASED PERMISSIONS (CHANGED to lowercase)
// =========================
$can_add = in_array($role_lower, ['admin','staff']);
$can_edit = in_array($role_lower, ['admin','staff']);
$can_delete = $role_lower === 'admin';
$can_view = in_array($role_lower, ['admin','staff','user']);

// =========================
// HARD BLOCK (ADDED from purchaseorder)
// =========================
if (!in_array($role_lower, ['admin','staff','user'])) {
    header("Location: dashboard.php");
    exit();
}

// Add Evaluation
if(isset($_POST['addEval']) && $can_add) {
    $vendor = $_POST['Vendor_ID'];
    $rating = $_POST['Rating'];
    $feed_Back = $_POST['Feed_Back'];
    $date = $_POST['Eval_Date'];

    $sql = "INSERT INTO PerformanceEvaluation (Vendor_ID, Rating, Feed_Back, Eval_Date)
            VALUES ('$vendor','$rating','$feed_Back','$date')";
    $conn->query($sql);
    $msg = "✅ Evaluation added successfully!";
}

// Update Evaluation
if(isset($_POST['updateEval']) && $can_edit) {
    $id = $_POST['Eval_ID'];
    $vendor = $_POST['Vendor_ID'];
    $rating = $_POST['Rating'];
    $feed_Back = $_POST['Feed_Back'];
    $date = $_POST['Eval_Date'];

    $sql = "UPDATE PerformanceEvaluation 
            SET Vendor_ID='$vendor', Rating='$rating', 
                Feed_Back='$feed_Back', Eval_Date='$date'
            WHERE Eval_ID='$id'";
    $conn->query($sql);
    $msg = "✅ Evaluation updated successfully!";
}

// Delete Evaluation
if(isset($_POST['deleteEval']) && $can_delete) {
    $id = $_POST['Eval_ID'];
    $sql = "DELETE FROM PerformanceEvaluation WHERE EvalID='$id'";
    $conn->query($sql);
    $msg = "✅ Evaluation deleted successfully!";
}

// Fetch Evaluations with Vendor Name
$result = $conn->query("
    SELECT PE.EvaluationID, V.VendorName, PE.Rating, PE.FeedBack, PE.EvalDate
    FROM perfomanceevaluation PE
    JOIN Vendor V ON PE.VendorID = V.VendorID
    ORDER BY PE.EvalDate DESC
");

// Fetch Vendors for dropdown
$vendorsList = $conn->query("SELECT * FROM Vendor");
$totalEvaluations = $result->num_rows;
$avgRating = $conn->query("SELECT AVG(Rating) as avg FROM perfomanceevaluation")->fetch_assoc()['avg'];

// Get edit evaluation
$editEval = null;
if(isset($_GET['edit']) && $can_edit) {
    $res = $conn->query("SELECT * FROM perfomanceevaluation WHERE EvalID='$_GET[edit]'");
    $editEval = $res->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Evaluation - VendorSphere</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ALL CSS STAYS THE SAME - NO CHANGES */
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --accent: #4cc9f0;
            --success: #4ade80;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #2b2d42;
            --light: #f8f9fa;
            --star-color: #fbbf24;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        /* Main Content */
        .main-content {
            padding: 30px;
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary), var(--accent));
        }

        .header-left h1 {
            color: var(--dark);
            font-size: 28px;
            font-weight: 600;
        }

        .header-left p {
            color: var(--dark);
            opacity: 0.7;
            margin-top: 5px;
            font-size: 14px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-buttons {
            display: flex;
            gap: 10px;
        }

        .header-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 14px;
        }

        .header-btn-dashboard {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .header-btn-logout {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .header-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
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
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .user-details {
            text-align: right;
        }

        .user-details .name {
            font-weight: 600;
            color: var(--dark);
        }

        .user-details .role {
            font-size: 12px;
            color: var(--dark);
            opacity: 0.7;
            background: var(--light);
            padding: 3px 10px;
            border-radius: 20px;
            display: inline-block;
        }

        /* Permission Indicator */
        .permission-indicator {
            background: rgba(67, 97, 238, 0.1);
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .permission-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .permission-item i {
            color: var(--success);
        }

        .permission-item.disabled i {
            color: var(--danger);
        }

        /* Stats Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 20px;
            color: white;
        }

        .stat-icon.evals { background: linear-gradient(135deg, var(--primary), var(--secondary)); }
        .stat-icon.rating { background: linear-gradient(135deg, var(--warning), #d97706); }
        .stat-icon.vendors { background: linear-gradient(135deg, var(--success), #10b981); }

        .stat-card h3 {
            font-size: 14px;
            color: var(--dark);
            opacity: 0.7;
            margin-bottom: 5px;
        }

        .stat-card .value {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            line-height: 1;
        }

        /* Action Buttons */
        .action-btns {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            font-size: 15px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success), #10b981);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning), #d97706);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .card-title {
            color: var(--dark);
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: var(--primary);
        }

        /* Forms */
        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: var(--dark);
            font-weight: 500;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group label i {
            color: var(--primary);
        }

        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        /* Star Rating */
        .star-rating {
            display: flex;
            gap: 10px;
            margin: 15px 0;
        }

        .star {
            cursor: pointer;
            font-size: 24px;
            color: #e5e7eb;
            transition: color 0.2s ease;
        }

        .star.active {
            color: var(--star-color);
        }

        /* Tables */
        .table-container {
            overflow-x: auto;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            border-radius: 15px;
            overflow: hidden;
        }

        thead {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }

        th {
            padding: 18px 20px;
            text-align: left;
            color: white;
            font-weight: 500;
            font-size: 15px;
        }

        tbody tr {
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.3s ease;
        }

        tbody tr:hover {
            background: #f8fafc;
            transform: scale(1.01);
        }

        td {
            padding: 18px 20px;
            color: var(--dark);
            font-size: 14px;
        }

        /* Rating Display */
        .rating-display {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .rating-display .star {
            font-size: 16px;
            color: var(--star-color);
        }

        /* Welcome Section */
        .welcome-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .welcome-icon {
            font-size: 60px;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .welcome-section h2 {
            color: var(--dark);
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .welcome-section p {
            color: var(--dark);
            opacity: 0.7;
            max-width: 600px;
            margin: 0 auto 30px;
            line-height: 1.6;
        }

        /* Alerts */
        .alert {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideIn 0.5s ease;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 5px solid var(--success);
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 5px solid var(--danger);
        }

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border-left: 5px solid var(--warning);
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .header-right {
                flex-direction: column;
                width: 100%;
            }
            
            .header-buttons {
                justify-content: center;
                width: 100%;
            }
            
            .user-info {
                flex-direction: column;
                text-align: center;
                width: 100%;
            }
            
            .action-btns {
                justify-content: center;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .welcome-section {
                padding: 30px 20px;
            }
            
            .permission-indicator {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

<!-- Main Content -->
<div class="main-content">
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-star"></i> Performance Evaluation</h1>
            <p>Evaluate and monitor vendor performance metrics</p>
        </div>
        
        <div class="header-right">
            <div class="header-buttons">
                <a href="dashboard.php" class="header-btn header-btn-dashboard">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="logout.php" class="header-btn header-btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
            
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['username'], 0, 2)) ?>
                </div>
                <div class="user-details">
                    <div class="name"><?= $_SESSION['username'] ?></div>
                    <!-- CHANGE: Display lowercase role -->
                    <div class="role"><?= $role_lower ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Permission Indicator -->
    <div class="permission-indicator">
        <div class="permission-item <?= $can_view ? '' : 'disabled' ?>">
            <i class="fas fa-<?= $can_view ? 'check-circle' : 'times-circle' ?>"></i>
            <span>View Access</span>
        </div>
        <div class="permission-item <?= $can_add ? '' : 'disabled' ?>">
            <i class="fas fa-<?= $can_add ? 'check-circle' : 'times-circle' ?>"></i>
            <span>Add Evaluations</span>
        </div>
        <div class="permission-item <?= $can_edit ? '' : 'disabled' ?>">
            <i class="fas fa-<?= $can_edit ? 'check-circle' : 'times-circle' ?>"></i>
            <span>Edit Evaluations</span>
        </div>
        <div class="permission-item <?= $can_delete ? '' : 'disabled' ?>">
            <i class="fas fa-<?= $can_delete ? 'check-circle' : 'times-circle' ?>"></i>
            <span>Delete Evaluations</span>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon evals">
                <i class="fas fa-chart-bar"></i>
            </div>
            <h3>Total Evaluations</h3>
            <div class="value"><?= $totalEvaluations ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon rating">
                <i class="fas fa-star"></i>
            </div>
            <h3>Average Rating</h3>
            <div class="value"><?= number_format($avgRating, 1) ?>/5</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon vendors">
                <i class="fas fa-store"></i>
            </div>
            <h3>Vendors Rated</h3>
            <div class="value"><?= $vendorsList->num_rows ?></div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if($msg): ?>
        <div class="alert <?= strpos($msg,'❌')!==false?'alert-danger':'alert-success' ?>">
            <i class="fas <?= strpos($msg,'❌')!==false?'fa-times-circle':'fa-check-circle' ?>"></i>
            <span><?= $msg ?></span>
        </div>
        <script>
            setTimeout(function(){
                const alert = document.querySelector('.alert');
                if(alert) alert.style.display = 'none';
            }, 5000);
        </script>
    <?php endif; ?>

    <?php if(!$can_view): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <span>You don't have permission to view evaluations. Please contact your administrator.</span>
        </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div class="action-btns">
        <?php if($can_add): ?>
        <a href="?action=add" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Add Evaluation
        </a>
        <?php endif; ?>
        
        <?php if($can_view): ?>
        <a href="?action=read" class="btn btn-success">
            <i class="fas fa-eye"></i> View Evaluations
        </a>
        <?php endif; ?>
        
        <?php if($can_edit): ?>
        <a href="?action=update" class="btn btn-warning">
            <i class="fas fa-edit"></i> Update Evaluation
        </a>
        <?php endif; ?>
        
        <?php if($can_delete): ?>
        <a href="?action=delete" class="btn btn-danger">
            <i class="fas fa-trash-alt"></i> Delete Evaluation
        </a>
        <?php endif; ?>
    </div>

    <!-- Default Welcome Section (When no action is selected) -->
    <!-- <?php if($action === '' && $can_view): ?>
    <div class="welcome-section">
        <div class="welcome-icon">
            <i class="fas fa-star"></i>
        </div>
        <h2>Welcome to Performance Evaluation</h2>
        <p>
            Manage all your vendor performance evaluations efficiently. Add new evaluations, view existing ones, 
            update details, or delete evaluations as needed. Use the action buttons above to get started.
        </p>
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <?php if($can_view): ?>
            <a href="?action=read" class="btn btn-success">
                <i class="fas fa-eye"></i> View All Evaluations
            </a>
            <?php endif; ?>
            <?php if($can_add): ?>
            <a href="?action=add" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Create New Evaluation
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php elseif($action === '' && !$can_view): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <span>You don't have permission to access evaluations. Please contact your administrator.</span>
    </div>
    <?php endif; ?> -->

    <!-- ADD Evaluation -->
    <?php if($action==='add' && $can_add): ?>
    <div class="card">
        <div class="card-title">
            <i class="fas fa-plus-circle"></i> Create New Evaluation
        </div>
        <form method="POST" id="evalForm">
            <div class="form-group">
                <label><i class="fas fa-store"></i> Select Vendor</label>
                <select name="VendorID" class="form-control" required>
                    <option value="">-- Select a Vendor --</option>
                    <?php $vendorsList->data_seek(0); while($v = $vendorsList->fetch_assoc()): ?>
                        <option value="<?= $v['VendorID'] ?>"><?= $v['VendorName'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-star"></i> Rating (1-5)</label>
                <div class="star-rating" id="starRating">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                        <span class="star" data-value="<?= $i ?>">
                            <i class="fas fa-star"></i>
                        </span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="Rating" id="ratingValue" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-comment"></i> Feedback</label>
                <textarea name="Feed_Back" class="form-control" rows="4" 
                          placeholder="Provide detailed feedback about the vendor's performance..." required></textarea>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-calendar-alt"></i> Evaluation Date</label>
                <input type="date" name="Eval_Date" class="form-control" required>
            </div>
            
            <button type="submit" name="addEval" class="btn btn-primary">
                <i class="fas fa-save"></i> Submit Evaluation
            </button>
        </form>
    </div>
    <?php elseif($action==='add' && !$can_add): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <span>You don't have permission to add evaluations. Please contact your administrator.</span>
    </div>
    <?php endif; ?>

    <!-- READ Evaluations -->
    <?php if($action==='read' && $can_view): ?>
    <div class="card">
        <div class="card-title">
            <i class="fas fa-list"></i> All Evaluations
            <span style="margin-left: auto; font-size: 14px; color: var(--dark); opacity: 0.7;">
                Showing <?= $totalEvaluations ?> evaluations
            </span>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Eval ID</th>
                        <th>Vendor</th>
                        <th>Rating</th>
                        <th>Feedback</th>
                        <th>Date</th>
                        <?php if($can_edit || $can_delete): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $result->data_seek(0); while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?= $row['EvaluationID'] ?></strong></td>
                        <td><?= $row['VendorName'] ?></td>
                        <td>
                            <div class="rating-display">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="<?= $i <= $row['Rating'] ? 'fas fa-star' : 'far fa-star' ?>" 
                                       style="color: var(--star-color);"></i>
                                <?php endfor; ?>
                                <span style="margin-left: 5px;"><?= $row['Rating'] ?>/5</span>
                            </div>
                        </td>
                        <td><?= substr($row['FeedBack'], 0, 50) ?>...</td>
                        <td><?= date('M d, Y', strtotime($row['EvalDate'])) ?></td>
                        <?php if($can_edit || $can_delete): ?>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <?php if($can_edit): ?>
                                
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                <?php if($can_delete): ?>
                                <form method="POST" onsubmit="return confirmDelete()" style="margin: 0;">
                                    <input type="hidden" name="EvalID" value="<?= $row['EvalID'] ?>">
                                    <button type="submit" name="deleteEval" class="btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php elseif($action==='read' && !$can_view): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <span>You don't have permission to view evaluations. Please contact your administrator.</span>
    </div>
    <?php endif; ?>

    <!-- UPDATE Evaluation -->
    <?php if($action==='update' && $can_edit): ?>
    <div class="card">
        <div class="card-title">
            <i class="fas fa-edit"></i> Update Evaluation - Select Evaluation
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Eval ID</th>
                        <th>Vendor</th>
                        <th>Rating</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $result->data_seek(0); while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?= $row['EvaluationID'] ?></strong></td>
                        <td><?= $row['VendorName'] ?></td>
                        <td>
                            <div class="rating-display">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="<?= $i <= $row['Rating'] ? 'fas fa-star' : 'far fa-star' ?>" 
                                       style="color: var(--star-color);"></i>
                                <?php endfor; ?>
                                <span style="margin-left: 5px;"><?= $row['Rating'] ?>/5</span>
                            </div>
                        </td>
                        <td><?= date('M d, Y', strtotime($row['EvalDate'])) ?></td>
                        <td>
                            
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if($editEval): ?>
    <div class="card">
        <div class="card-title">
            <i class="fas fa-edit"></i> Edit Evaluation #<?= $editEval['Eval_ID'] ?>
        </div>
        <form method="POST">
            <input type="hidden" name="EvalID" value="<?= $editEval['Eval_ID'] ?>">
            
            <div class="form-group">
                <label><i class="fas fa-store"></i> Select Vendor</label>
                <select name="Vendor_ID" class="form-control" required>
                    <?php $vendorsList->data_seek(0); while($v = $vendorsList->fetch_assoc()): ?>
                        <option value="<?= $v['Vendor_ID'] ?>" <?= $editEval['Vendor_ID']==$v['Vendor_ID']?'selected':'' ?>>
                            <?= $v['Vendor_Name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-star"></i> Rating (1-5)</label>
                <div class="star-rating" id="editStarRating">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                        <span class="star edit-star" data-value="<?= $i ?>" 
                              <?= $i <= $editEval['Rating'] ? 'style="color: var(--star-color);"' : '' ?>>
                            <i class="<?= $i <= $editEval['Rating'] ? 'fas fa-star' : 'far fa-star' ?>"></i>
                        </span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="Rating" id="editRatingValue" value="<?= $editEval['Rating'] ?>" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-comment"></i> Feedback</label>
                <textarea name="FeedBack" class="form-control" rows="4" required><?= $editEval['Feed_Back'] ?></textarea>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-calendar-alt"></i> Evaluation Date</label>
                <input type="date" name="EvalDate" class="form-control" 
                       value="<?= $editEval['EvalDate'] ?>" required>
            </div>
            
            <div style="display: flex; gap: 15px;">
                <button type="submit" name="updateEval" class="btn btn-warning">
                    <i class="fas fa-sync-alt"></i> Update Evaluation
                </button>
                <a href="?action=update" class="btn" style="background: #6b7280; color: white;">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>
    <?php elseif($action==='update' && !$can_edit): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <span>You don't have permission to update evaluations. Please contact your administrator.</span>
    </div>
    <?php endif; ?>

    <!-- DELETE Evaluation -->
    <?php if($action==='delete' && $can_delete): ?>
    <div class="card">
        <div class="card-title">
            <i class="fas fa-trash-alt"></i> Delete Evaluation
        </div>
        
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <span><strong>Warning:</strong> Deleting an evaluation cannot be undone. Please proceed with caution.</span>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Eval ID</th>
                        <th>Vendor</th>
                        <th>Rating</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $result->data_seek(0); while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?= $row['EvalID'] ?></strong></td>
                        <td><?= $row['VendorName'] ?></td>
                          <div class="rating-display">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="<?= $i <= $row['Rating'] ? 'fas fa-star' : 'far fa-star' ?>" 
                                       style="color: var(--star-color);"></i>
                                <?php endfor; ?>
                                <span style="margin-left: 5px;"><?= $row['Rating'] ?>/5</span>
                            </div>
                        </td>
                        <td><?= date('M d, Y', strtotime($row['EvalDate'])) ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirmDelete()" style="margin: 0;">
                                <input type="hidden" name="EvalID" value="<?= $row['EvalID'] ?>">
                                <button type="submit" name="deleteEval" class="btn-sm btn-danger">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Star Rating System for Add
const stars = document.querySelectorAll('#starRating .star');
const ratingInput = document.getElementById('ratingValue');

if(stars.length > 0) {
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const value = parseInt(this.getAttribute('data-value'));
            ratingInput.value = value;
            
            stars.forEach(s => {
                const starValue = parseInt(s.getAttribute('data-value'));
                if(starValue <= value) {
                    s.style.color = 'var(--star-color)';
                    s.querySelector('i').className = 'fas fa-star';
                } else {
                    s.style.color = '#e5e7eb';
                    s.querySelector('i').className = 'far fa-star';
                }
            });
        });
    });
}

// Star Rating System for Edit
const editStars = document.querySelectorAll('.edit-star');
const editRatingInput = document.getElementById('editRatingValue');

if(editStars.length > 0) {
    editStars.forEach(star => {
        star.addEventListener('click', function() {
            const value = parseInt(this.getAttribute('data-value'));
            editRatingInput.value = value;
            
            editStars.forEach(s => {
                const starValue = parseInt(s.getAttribute('data-value'));
                if(starValue <= value) {
                    s.style.color = 'var(--star-color)';
                    s.querySelector('i').className = 'fas fa-star';
                } else {
                    s.style.color = '#e5e7eb';
                    s.querySelector('i').className = 'far fa-star';
                }
            });
        });
    });
}

function confirmDelete() {
    return confirm('⚠️ Are you sure you want to delete this evaluation?\nThis action cannot be undone.');
}
</script>

</body>
</html>