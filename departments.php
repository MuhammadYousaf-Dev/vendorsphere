<?php
include 'auth.php';
include 'db.php';

// CHANGE: Use lowercase like purchaseorder
$role_lower = strtolower($_SESSION['role']);
$action = $_GET['action'] ?? '';
$msg = '';

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

/* ADD */
if(isset($_POST['addDept']) && $can_add){
    $name = $_POST['DeptName'];
    $conn->query("INSERT INTO departments (DeptName) VALUES ('$name')");
    $msg = "✅ Department added successfully!";
}

/* UPDATE */
if(isset($_POST['updateDept']) && $can_edit){
    $id = $_POST['DepartmentID'];
    $name = $_POST['DeptName'];
    $conn->query("UPDATE department SET DeptName='$name' WHERE DepartmentID='$id'");
    $msg = "✅ Department updated successfully!";
}

/* DELETE */
if(isset($_POST['deleteDept']) && $can_delete){
    $id = $_POST['DepartmentID'];
    $conn->query("DELETE FROM departments WHERE DepartmentID='$id'");
    $msg = "✅ Department deleted successfully!";
}

$department = $conn->query("SELECT * FROM department");


$editDept = null;
if(isset($_GET['edit']) && $can_edit){
    $res = $conn->query("SELECT * FROM department WHERE DepartmentID='$_GET[edit]'");
    $editDept = $res->fetch_assoc();
}

// Reset pointer for reuse
$department->data_seek(0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments - VendorSphere</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --accent: #4cc9f0;
            --success: #4ade80;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #2b2d42;
            --light: #f8f9fa;
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

        .stat-icon.dept { background: linear-gradient(135deg, var(--primary), var(--secondary)); }
        .stat-icon.active { background: linear-gradient(135deg, var(--success), #10b981); }
        .stat-icon.categories { background: linear-gradient(135deg, var(--warning), #d97706); }

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

        /* Status Badges */
        .status-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-active { background: #dcfce7; color: #166534; }
        .status-inactive { background: #fee2e2; color: #991b1b; }

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
            <h1><i class="fas fa-building"></i> Departments</h1>
            <p>Manage your department database and relationships</p>
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
                    <!-- CHANGE: Display lowercase role like purchaseorder -->
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
            <span>Add Departments</span>
        </div>
        <div class="permission-item <?= $can_edit ? '' : 'disabled' ?>">
            <i class="fas fa-<?= $can_edit ? 'check-circle' : 'times-circle' ?>"></i>
            <span>Edit Departments</span>
        </div>
        <div class="permission-item <?= $can_delete ? '' : 'disabled' ?>">
            <i class="fas fa-<?= $can_delete ? 'check-circle' : 'times-circle' ?>"></i>
            <span>Delete Departments</span>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon dept">
                <i class="fas fa-building"></i>
            
        
        <div class="stat-card">
            <div class="stat-icon active">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3>Active Contracts</h3>
            <div class="value">0</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon categories">
                <i class="fas fa-layer-group"></i>
            </div>
            <h3>Categories</h3>
            <div class="value">8</div>
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
            <span>You don't have permission to view departments. Please contact your administrator.</span>
        </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div class="action-btns">
        <?php if($can_add): ?>
        <a href="?action=add" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Add Department
        </a>
        <?php endif; ?>
        
        <?php if($can_view): ?>
        <a href="?action=read" class="btn btn-success">
            <i class="fas fa-eye"></i> View Departments
        </a>
        <?php endif; ?>
        
        <?php if($can_edit): ?>
        <a href="?action=update" class="btn btn-warning">
            <i class="fas fa-edit"></i> Update Department
        </a>
        <?php endif; ?>
        
        <?php if($can_delete): ?>
        <a href="?action=delete" class="btn btn-danger">
            <i class="fas fa-trash-alt"></i> Delete Department
        </a>
        <?php endif; ?>
    </div>

    <!-- Default Welcome Section (When no action is selected) -->
    <!-- <?php if($action === '' && $can_view): ?>
    <div class="welcome-section">
        <div class="welcome-icon">
            <i class="fas fa-building"></i>
        </div>
        <h2>Welcome to Department Management</h2>
        <p>
            Manage all your departments efficiently. Add new departments, view existing ones, 
            update details, or delete departments as needed. Use the action buttons above to get started.
        </p>
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <?php if($can_view): ?>
            <a href="?action=read" class="btn btn-success">
                <i class="fas fa-eye"></i> View All Departments
            </a>
            <?php endif; ?>
            <?php if($can_add): ?>
            <a href="?action=add" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Create New Department
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php elseif($action === '' && !$can_view): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <span>You don't have permission to access departments. Please contact your administrator.</span>
    </div>
    <?php endif; ?> -->

    <!-- READ Departments -->
    <?php if($action==='read' && $can_view): ?>
    <div class="card">
        <div class="card-title">
            <i class="fas fa-list"></i> All Departments
            <span style="margin-left: auto; font-size: 14px; color: var(--dark); opacity: 0.7;">
                
            </span>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Department ID</th>
                        <th>Department Name</th>
                        <th>Status</th>
                        <?php if($can_edit || $can_delete): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $department->data_seek(0); while($dept=$department->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?= $dept['DepartmentID'] ?></strong></td>
                        <td><?= $dept['DeptName'] ?></td>
                        <td>
                            <span class="status-badge status-active">
                                Active
                            </span>
                        </td>
                        <?php if($can_edit || $can_delete): ?>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <?php if($can_edit): ?>
                                
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                <?php if($can_delete): ?>
                                <form method="POST" onsubmit="return confirmDelete()" style="margin: 0;">
                                    <input type="hidden" name="DepartmentID" value="<?= $dept['DepartmentID'] ?>">
                                    <button type="submit" name="deleteDept" class="btn-sm btn-danger">
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
        <span>You don't have permission to view departments. Please contact your administrator.</span>
    </div>
    <?php endif; ?>

    <!-- ADD Department -->
    <?php if($action==='add' && $can_add): ?>
    <div class="card">
        <div class="card-title">
            <i class="fas fa-plus-circle"></i> Create New Department
        </div>
        <form method="POST">
            <div class="form-group">
                <label><i class="fas fa-building"></i> Department Name</label>
                <input type="text" name="Department_Name" class="form-control" 
                       placeholder="Enter department name" required>
            </div>
            
            <button type="submit" name="addDept" class="btn btn-primary">
                <i class="fas fa-save"></i> Create Department
            </button>
        </form>
    </div>
    <?php elseif($action==='add' && !$can_add): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <span>You don't have permission to add departments. Please contact your administrator.</span>
    </div>
    <?php endif; ?>

    <!-- UPDATE Department -->
    <?php if($action==='update' && $can_edit): ?>
    <div class="card">
        <div class="card-title">
            <i class="fas fa-edit"></i> Update Department - Select Department
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Department ID</th>
                        <th>Department Name</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $department->data_seek(0); while($dept=$department->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?= $dept['DepartmentID'] ?></strong></td>
                        <td><?= $dept['DeptName'] ?></td>
                        <td>
                            <a href="?action=update&edit=<?= $dept['DepartmentID'] ?>" class="btn-sm btn-warning">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if($editDept): ?>
    <div class="card">
        <div class="card-title">
            <i class="fas fa-edit"></i> Edit Department #<?= $editDept['DepartmentID'] ?>
        </div>
        <form method="POST">
            <input type="hidden" name="DepartmentID" value="<?= $editDept['DepartmentID'] ?>">
            
            <div class="form-group">
                <label><i class="fas fa-building"></i> Department Name</label>
                <input type="text" name="DeptName" class="form-control" 
                       value="<?= $editDept['DeptName'] ?>" required>
            </div>
            
            <div style="display: flex; gap: 15px;">
                <button type="submit" name="updateDept" class="btn btn-warning">
                    <i class="fas fa-sync-alt"></i> Update Department
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
        <span>You don't have permission to update departments. Please contact your administrator.</span>
    </div>
    <?php endif; ?>

    <!-- DELETE Department -->
    <?php if($action==='delete' && $can_delete): ?>
    <div class="card">
        <div class="card-title">
            <i class="fas fa-trash-alt"></i> Delete Department
        </div>
        
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <span><strong>Warning:</strong> Deleting a department cannot be undone. Please proceed with caution.</span>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Department ID</th>
                        <th>Department Name</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $department->data_seek(0); while($dept=$department->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?= $dept['DepartmentID'] ?></strong></td>
                        <td><?= $dept['DepartmentName'] ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirmDelete()" style="margin: 0;">
                                <input type="hidden" name="DepartmentID" value="<?= $dept['DepartmentID'] ?>">
                                <button type="submit" name="deleteDept" class="btn-sm btn-danger">
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
    <?php elseif($action==='delete' && !$can_delete): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <span>You don't have permission to delete departments. Please contact your administrator.</span>
    </div>
    <?php endif; ?>
</div>

<script>
function confirmDelete() {
    return confirm('⚠️ Are you sure you want to delete this department?\nThis action cannot be undone.');
}
</script>

</body>
</html>