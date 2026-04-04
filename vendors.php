<?php
include 'auth.php';
include 'db.php';

// Include config for role permissions
include 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$action = $_GET['action'] ?? '';
$role   = $_SESSION['role'];
$msg = '';

// Check if user has permission to access vendors
if (!canAccess('vendor.php', $role)) {
    header("Location: dashboard.php?error=access_denied");
    exit();
}

// Role-based permissions
$can_add = hasPermission($role, 'create');
$can_edit = hasPermission($role, 'edit');
$can_delete = hasPermission($role, 'delete');
$can_view = hasPermission($role, 'view');

/* ADD - Only if permission */
if($can_add && isset($_POST['addVendor'])){
    $conn->query("
        INSERT INTO vendor (Vendor_Name, Contact_Info, Service_Category, Certifications)
        VALUES (
            '{$_POST['Vendor_Name']}',
            '{$_POST['Contact_Info']}',
            '{$_POST['Service_Category']}',
            '{$_POST['Certifications']}'
        )
    ");
    $msg = "✅ Vendor added successfully!";
}

/* UPDATE - Only if permission */
if($can_edit && isset($_POST['updateVendor'])){
    $conn->query("
        UPDATE vendor SET
            Vendor_Name='{$_POST['Vendor_Name']}',
            Contact_Info='{$_POST['Contact_Info']}',
            Service_Category='{$_POST['Service_Category']}',
            Certifications='{$_POST['Certifications']}'
        WHERE Vendor_ID='{$_POST['VendorID']}'
    ");
    $msg = "✅ Vendor updated successfully!";
}

/* DELETE - Only if permission */
if($can_delete && isset($_POST['deleteVendor'])){
    $conn->query("DELETE FROM vendor WHERE Vendor_ID='{$_POST['Vendor_ID']}'");
    $msg = "✅ Vendor deleted successfully!";
}

/* FETCH - Always allowed for view */
$vendors = $conn->query("SELECT * FROM vendor ORDER BY Vendor_ID DESC");

$editVendor = null;
if(isset($_GET['edit'])){
    $res = $conn->query("SELECT * FROM vendor WHERE Vendor_ID='{$_GET['edit']}'");
    $editVendor = $res->fetch_assoc();
}

// Get stats
$totalVendors = $vendors->num_rows;
$vendorsByCategory = $conn->query("SELECT Service_Category, COUNT(*) as count FROM vendor GROUP BY Service_Category");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendors - VendorSphere</title>
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

        .stat-icon.vendors { background: linear-gradient(135deg, var(--primary), var(--secondary)); }
        .stat-icon.contracts { background: linear-gradient(135deg, var(--success), #10b981); }
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
        }
    </style>
</head>
<body>

<!-- Main Content -->
<div class="main-content">
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-store"></i> Vendors</h1>
            <p>Manage your vendor database and relationships</p>
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
                    <div class="role"><?= $role ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon vendors">
                <i class="fas fa-store"></i>
            </div>
            <h3>Total Vendors</h3>
            <div class="value"><?= $totalVendors ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon contracts">
                <i class="fas fa-file-contract"></i>
            </div>
            <h3>Active Contracts</h3>
            <div class="value"><?= $conn->query("SELECT COUNT(*) as count FROM contract")->fetch_assoc()['count'] ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon categories">
                <i class="fas fa-tags"></i>
            </div>
            <h3>Categories</h3>
            <div class="value"><?= $vendorsByCategory->num_rows ?></div>
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

    <!-- Action Buttons -->
    <div class="action-btns">
        <?php if($can_add): ?>
        <a href="?action=add" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Add Vendor
        </a>
        <?php endif; ?>
        
        <?php if($can_view): ?>
        <a href="?action=read" class="btn btn-success">
            <i class="fas fa-eye"></i> View Vendors
        </a>
        <?php endif; ?>
        
        <?php if($can_edit): ?>
        <a href="?action=update" class="btn btn-warning">
            <i class="fas fa-edit"></i> Update Vendor
        </a>
        <?php endif; ?>
        
        <?php if($can_delete): ?>
        <a href="?action=delete" class="btn btn-danger">
            <i class="fas fa-trash-alt"></i> Delete Vendor
        </a>
        <?php endif; ?>
    </div>

    <!-- Default Welcome Section (When no action is selected) -->
    <!-- <?php if($action === ''): ?>
    <div class="welcome-section">
        <div class="welcome-icon">
            <i class="fas fa-store"></i>
        </div>
        <h2>Welcome to Vendor Management</h2>
        <p>
            Manage all your vendors efficiently. Add new vendors, view existing ones, 
            update details, or delete vendors as needed. Use the action buttons above to get started.
        </p>
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <?php if($can_view): ?>
            <a href="?action=read" class="btn btn-success">
                <i class="fas fa-eye"></i> View All Vendors
            </a>
            <?php endif; ?>
            <?php if($can_add): ?>
            <a href="?action=add" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Create New Vendor
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?> -->

    <!-- READ Vendors -->
    <?php if($action==='read' && $can_view): ?>
    <div class="card">
        <div class="card-title">
            <i class="fas fa-list"></i> All Vendors
            <span style="margin-left: auto; font-size: 14px; color: var(--dark); opacity: 0.7;">
                <?= $totalVendors ?> vendors found
            </span>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Vendor ID</th>
                        <th>Vendor Name</th>
                        <th>Contact Info</th>
                        <th>Service Category</th>
                        <th>Certifications</th>
                        <?php if($can_edit || $can_delete): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $vendors->data_seek(0); while($v=$vendors->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?= $v['Vendor_ID'] ?></strong></td>
                        <td><?= $v['Vendor_Name'] ?></td>
                        <td><?= $v['Contact_Info'] ?: 'N/A' ?></td>
                        <td><?= $v['Service_Category'] ?: 'N/A' ?></td>
                        <td><?= $v['Certifications'] ?: 'N/A' ?></td>
                        <?php if($can_edit || $can_delete): ?>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <?php if($can_edit): ?>
                                <a href="?action=update&edit=<?= $v['Vendor_ID'] ?>" class="btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                <?php if($can_delete): ?>
                                <form method="POST" onsubmit="return confirmDelete()" style="margin: 0;">
                                    <input type="hidden" name="Vendor_ID" value="<?= $v['Vendor_ID'] ?>">
                                    <button type="submit" name="deleteVendor" class="btn-sm btn-danger">
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
        <span>You don't have permission to view vendors. Please contact your administrator.</span>
    </div>
    <?php endif; ?>

    <!-- ADD Vendor -->
    <?php if($action==='add' && $can_add): ?>
    <div class="card">
        <div class="card-title">
            <i class="fas fa-plus-circle"></i> Create New Vendor
        </div>
        <form method="POST">
            <div class="form-group">
                <label><i class="fas fa-store"></i> Vendor Name</label>
                <input type="text" name="Vendor_Name" class="form-control" 
                       placeholder="Enter vendor company name" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-phone"></i> Contact Information</label>
                <input type="text" name="Contact_Info" class="form-control" 
                       placeholder="Phone, email, or other contact details">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-tags"></i> Service Category</label>
                <input type="text" name="Service_Category" class="form-control" 
                       placeholder="e.g., IT Services, Logistics, Manufacturing">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-certificate"></i> Certifications</label>
                <input type="text" name="Certifications" class="form-control" 
                       placeholder="ISO, Quality, or other certifications">
            </div>
            
            <button type="submit" name="addVendor" class="btn btn-primary">
                <i class="fas fa-save"></i> Create Vendor
            </button>
        </form>
    </div>
    <?php elseif($action==='add' && !$can_add): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <span>You don't have permission to add vendors. Please contact your administrator.</span>
    </div>
    <?php endif; ?>

    <!-- UPDATE Vendor -->
    <?php if($action==='update' && $can_edit): ?>
    <div class="card">
        <div class="card-title">
            <i class="fas fa-edit"></i> Update Vendor - Select Vendor
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Vendor ID</th>
                        <th>Vendor Name</th>
                        <th>Service Category</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $vendors->data_seek(0); while($v=$vendors->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?= $v['Vendor_ID'] ?></strong></td>
                        <td><?= $v['Vendor_Name'] ?></td>
                        <td><?= $v['Service_Category'] ?: 'N/A' ?></td>
                        <td>
                            <a href="?action=update&edit=<?= $v['Vendor_ID'] ?>" class="btn-sm btn-warning">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if($editVendor): ?>
    <div class="card">
        <div class="card-title">
            <i class="fas fa-edit"></i> Edit Vendor #<?= $editVendor['Vendor_ID'] ?>
        </div>
        <form method="POST">
            <input type="hidden" name="Vendor_ID" value="<?= $editVendor['Vendor_ID'] ?>">
            
            <div class="form-group">
                <label><i class="fas fa-store"></i> Vendor Name</label>
                <input type="text" name="Vendor_Name" class="form-control" 
                       value="<?= $editVendor['Vendor_Name'] ?>" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-phone"></i> Contact Information</label>
                <input type="text" name="Contact_Info" class="form-control" 
                       value="<?= $editVendor['Contact_Info'] ?>">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-tags"></i> Service Category</label>
                <input type="text" name="Service_Category" class="form-control" 
                       value="<?= $editVendor['Service_Category'] ?>">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-certificate"></i> Certifications</label>
                <input type="text" name="Certifications" class="form-control" 
                       value="<?= $editVendor['Certifications'] ?>">
            </div>
            
            <div style="display: flex; gap: 15px;">
                <button type="submit" name="updateVendor" class="btn btn-warning">
                    <i class="fas fa-sync-alt"></i> Update Vendor
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
        <span>You don't have permission to update vendors. Please contact your administrator.</span>
    </div>
    <?php endif; ?>

    <!-- DELETE Vendor -->
    <?php if($action==='delete' && $can_delete): ?>
    <div class="card">
        <div class="card-title">
            <i class="fas fa-trash-alt"></i> Delete Vendor - Admin Only
        </div>
        
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <span><strong>Warning:</strong> Deleting a vendor cannot be undone. Please proceed with caution.</span>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Vendor ID</th>
                        <th>Vendor Name</th>
                        <th>Service Category</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $vendors->data_seek(0); while($v=$vendors->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?= $v['Vendor_ID'] ?></strong></td>
                        <td><?= $v['Vendor_Name'] ?></td>
                        <td><?= $v['Service_Category'] ?: 'N/A' ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirmDelete()" style="margin: 0;">
                                <input type="hidden" name="Vendor_ID" value="<?= $v['Vendor_ID'] ?>">
                                <button type="submit" name="deleteVendor" class="btn-sm btn-danger">
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
        <span>You don't have permission to delete vendors. Please contact your administrator.</span>
    </div>
    <?php endif; ?>
</div>

<script>
function confirmDelete() {
    return confirm('⚠️ Are you sure you want to delete this vendor?\nThis action cannot be undone.');
}
</script>

</body>
</html>