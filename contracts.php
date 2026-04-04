<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
include 'db.php';

// Role set karo
$role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'User';
$role_lower = strtolower($role);

$action = $_GET['action'] ?? '';
$msg = '';

// Check access
if (!in_array($role_lower, ['admin','staff','user'])) {
    echo "<script>
        alert('Access Denied! You need Admin, Staff or User privileges.');
        window.location.href='dashboard.php';
    </script>";
    exit();
}

/* ================= FETCH DATA ================= */
$vendorsList = $conn->query("SELECT * FROM vendor");
$contracts = $conn->query("
    SELECT c.*, v.VendorName 
    FROM contract c 
    JOIN vendor v ON c.VendorID = v.VendorID 
    ORDER BY c.ContractID DESC
");

$total_contracts = $contracts->num_rows;
$vendor_count = $conn->query("SELECT COUNT(*) as count FROM vendor")->fetch_assoc()['count'];

/* ================= CRUD OPERATIONS ================= */
// ADD Contract
if(isset($_POST['addContract']) && in_array($role_lower,['admin','staff'])){
    $vendor_id = $conn->real_escape_string($_POST['VendorID']);
    $start = $conn->real_escape_string($_POST['StartDate']);
    $end = $conn->real_escape_string($_POST['EndDate']);
    $terms = $conn->real_escape_string($_POST['Terms']);

    if($end < $start){
        $msg = "❌ End Date cannot be earlier than Start Date!";
    } else {
        $sql = "INSERT INTO contract (VendorID, Start_Date, EndDate, Terms)
                VALUES ('$vendor_id', '$start', '$end', '$terms')";
        
        if($conn->query($sql)) {
            $msg = "✅ Contract added successfully!";
            // Refresh
            $contracts = $conn->query("SELECT c.*, v.VendorName FROM contract c JOIN vendor v ON c.VendorID = v.Vendor_ID ORDER BY c.ContractID DESC");
            $total_contracts = $contracts->num_rows;
        } else {
            $msg = "❌ Error: " . $conn->error;
        }
    }
}

// UPDATE Contract
if(isset($_POST['updateContract']) && in_array($role_lower,['admin','staff'])){
    $contract_id = $conn->real_escape_string($_POST['ContractID']);
    $vendor_id = $conn->real_escape_string($_POST['VendorID']);
    $start = $conn->real_escape_string($_POST['StartDate']);
    $end = $conn->real_escape_string($_POST['EndDate']);
    $terms = $conn->real_escape_string($_POST['TermsConditions']);

    if($end < $start){
        $msg = "❌ End Date cannot be earlier than Start Date!";
    } else {
        $sql = "UPDATE contract SET
                VendorID='$vendor_id',
                StartDate='$start',
                EndDate='$end',
                Terms='$terms'
                WHERE ContractID='$contract_id'";
        
        if($conn->query($sql)) {
            $msg = "✅ Contract updated successfully!";
            // Refresh
            $contracts = $conn->query("SELECT c.*, v.VendorName FROM contract c JOIN vendor v ON c.VendorID = v.VendorID ORDER BY c.ContractID DESC");
        } else {
            $msg = "❌ Error: " . $conn->error;
        }
    }
}

// DELETE Contract
if(isset($_POST['deleteContract']) && $role_lower === 'admin'){
    $contract_id = $conn->real_escape_string($_POST['ContractID']);
    
    $sql = "DELETE FROM contract WHERE ContractID='$contract_id'";
    
    if($conn->query($sql)) {
        $msg = "✅ Contract deleted successfully!";
        // Refresh
        $contracts = $conn->query("SELECT c.*, v.VendorName FROM contract c JOIN vendor v ON c.VendorID = v.VendorID ORDER BY c.ContractID DESC");
        $total_contracts = $contracts->num_rows;
    } else {
        $msg = "❌ Error: " . $conn->error;
    }
}

// For edit
$editContract = null;
if(isset($_GET['edit']) && in_array($role_lower,['admin','staff'])){
    $edit_id = $conn->real_escape_string($_GET['edit']);
    $editContract = $conn->query(
        "SELECT * FROM contract WHERE ContractID='$edit_id'"
    )->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contracts - VendorSphere</title>
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

        .stat-icon.contracts { background: linear-gradient(135deg, var(--primary), var(--secondary)); }
        .stat-icon.vendors { background: linear-gradient(135deg, var(--success), #10b981); }
        .stat-icon.active { background: linear-gradient(135deg, var(--warning), #d97706); }

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
        .status-expired { background: #fee2e2; color: #991b1b; }

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
        }
    </style>
</head>
<body>

<!-- Main Content -->
<div class="main-content">
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-file-contract"></i> Contracts</h1>
            <p>Manage all vendor contracts and agreements</p>
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
                    <?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 2)) ?>
                </div>
                <div class="user-details">
                    <div class="name"><?= $_SESSION['username'] ?? 'User' ?></div>
                    <div class="role"><?= ucfirst($role) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon contracts">
                <i class="fas fa-file-contract"></i>
            </div>
            <h3>Total Contracts</h3>
            <div class="value"><?= $total_contracts ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon vendors">
                <i class="fas fa-users"></i>
            </div>
            <h3>Total Vendors</h3>
            <div class="value"><?= $vendor_count ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon active">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3>Active Contracts</h3>
            <div class="value">
                <?php 
                $active_count = $conn->query("
                    SELECT COUNT(*) as count FROM contract 
                    WHERE EndDate >= CURDATE()
                ")->fetch_assoc()['count'];
                echo $active_count;
                ?>
            </div>
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
        <?php if(in_array($role_lower, ['admin','staff'])): ?>
        <a href="?action=add" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Add Contract
        </a>
        <?php endif; ?>
        
        <a href="?action=read" class="btn btn-success">
            <i class="fas fa-eye"></i> View Contracts
        </a>
        
        <?php if(in_array($role_lower, ['admin','staff'])): ?>
        <a href="?action=update" class="btn btn-warning">
            <i class="fas fa-edit"></i> Update Contract
        </a>
        <?php endif; ?>
        
        <?php if($role_lower === 'admin'): ?>
        <a href="?action=delete" class="btn btn-danger">
            <i class="fas fa-trash-alt"></i> Delete Contract
        </a>
        <?php endif; ?>
    </div>

    <!-- Default Welcome Section (When no action is selected) -->
    <!-- <?php if($action === ''): ?>
    <div class="welcome-section">
        <div class="welcome-icon">
            <i class="fas fa-file-contract"></i>
        </div>
        <h2>Welcome to Contract Management</h2>
        <p>
            Manage all your vendor contracts efficiently. Add new contracts, view existing ones, 
            update details, or delete contracts as needed. Use the action buttons above to get started.
        </p>
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <a href="?action=read" class="btn btn-success">
                <i class="fas fa-eye"></i> View All Contracts
            </a>
            <?php if(in_array($role_lower, ['admin','staff'])): ?>
            <a href="?action=add" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Create New Contract
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?> -->

    <!-- READ Contracts -->
    <?php if($action==='read'): ?>
    <div class="card">
        <div class="card-title">
            <i class="fas fa-list"></i> All Contracts
            <span style="margin-left: auto; font-size: 14px; color: var(--dark); opacity: 0.7;">
                <?= $total_contracts ?> contracts found
            </span>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Contract ID</th>
                        <th>Vendor</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Terms Preview</th>
                        <th>Status</th>
                        <?php if(in_array($role_lower, ['admin','staff'])): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $contracts->data_seek(0);
                    while($c = $contracts->fetch_assoc()): 
                        $endDate = new DateTime($c['EndDate']);
                        $today = new DateTime();
                        $status = $endDate < $today ? 'Expired' : 'Active';
                    ?>
                    <tr>
                        <td><strong>#CT-<?= str_pad($c['ContractID'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                        <td><?= htmlspecialchars($c['VendorName']) ?></td>
                        <td><?= date('M d, Y', strtotime($c['StartDate'])) ?></td>
                        <td><?= date('M d, Y', strtotime($c['EndDate'])) ?></td>
                        <td><?= htmlspecialchars(substr($c['Terms'], 0, 40)) ?>...</td>
                        <td>
                            <span class="status-badge status-<?= strtolower($status) ?>">
                                <?= $status ?>
                            </span>
                        </td>
                        <?php if(in_array($role_lower, ['admin','staff'])): ?>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <a href="?action=update&edit=<?= $c['ContractID'] ?>" class="btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if($role_lower === 'admin'): ?>
                                <form method="POST" onsubmit="return confirmDelete()" style="margin: 0;">
                                    <input type="hidden" name="ContractID" value="<?= $c['ContractID'] ?>">
                                    <button type="submit" name="deleteContract" class="btn-sm btn-danger">
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
    <?php endif; ?>

    <!-- ADD Contract -->
    <?php if($action==='add' && in_array($role_lower,['admin','staff'])): ?>
    <div class="card">
        <div class="card-title">
            <i class="fas fa-plus-circle"></i> Create New Contract
        </div>
        <form method="POST">
            <div class="form-group">
                <label><i class="fas fa-store"></i> Select Vendor</label>
                <select name="Vendor_ID" class="form-control" required>
                    <option value="">-- Select a Vendor --</option>
                    <?php $vendorsList->data_seek(0); while($v=$vendorsList->fetch_assoc()): ?>
                        <option value="<?= $v['Vendor_ID'] ?>"><?= htmlspecialchars($v['Vendor_Name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Start Date</label>
                    <input type="date" name="Start_Date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> End Date</label>
                    <input type="date" name="End_Date" class="form-control" required>
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-file-alt"></i> Terms & Conditions</label>
                <textarea name="Terms_Conditions" class="form-control" rows="5" 
                          placeholder="Enter contract terms and conditions..." required></textarea>
            </div>
            
            <button type="submit" name="addContract" class="btn btn-primary">
                <i class="fas fa-save"></i> Create Contract
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- UPDATE Contract -->
    <?php if($action==='update' && in_array($role_lower,['admin','staff'])): ?>
    <div class="card">
        <div class="card-title">
            <i class="fas fa-edit"></i> Update Contract - Select Contract
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Contract ID</th>
                        <th>Vendor</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $contracts->data_seek(0); while($c=$contracts->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#CT-<?= str_pad($c['ContractID'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                        <td><?= htmlspecialchars($c['VendorName']) ?></td>
                        <td><?= date('M d, Y', strtotime($c['StartDate'])) ?></td>
                        <td><?= date('M d, Y', strtotime($c['EndDate'])) ?></td>
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

    <?php if($editContract): ?>
    <div class="card">
        <div class="card-title">
            <i class="fas fa-edit"></i> Edit Contract #CT-<?= str_pad($editContract['ContractID'], 4, '0', STR_PAD_LEFT) ?>
        </div>
        <form method="POST">
            <input type="hidden" name="ContractID" value="<?= $editContract['ContractID'] ?>">
            
            <div class="form-group">
                <label><i class="fas fa-store"></i> Select Vendor</label>
                <select name="VendorID" class="form-control" required>
                    <?php $vendorsList->data_seek(0); while($v=$vendorsList->fetch_assoc()): ?>
                        <option value="<?= $v['VendorID'] ?>" <?= $v['VendorID'] == $editContract['VendorID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($v['VendorName']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Start Date</label>
                    <input type="date" name="StartDate" class="form-control" 
                           value="<?= $editContract['StartDate'] ?>" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> End Date</label>
                    <input type="date" name="EndDate" class="form-control" 
                           value="<?= $editContract['EndDate'] ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-file-alt"></i> Terms & Conditions</label>
                <textarea name="Terms_Conditions" class="form-control" rows="5" required><?= htmlspecialchars($editContract['Terms_Conditions']) ?></textarea>
            </div>
            
            <div style="display: flex; gap: 15px;">
                <button type="submit" name="updateContract" class="btn btn-warning">
                    <i class="fas fa-sync-alt"></i> Update Contract
                </button>
                <a href="?action=update" class="btn" style="background: #6b7280; color: white;">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- DELETE Contract (admin Only) -->
    <?php if($action==='delete' && $role_lower==='admin'): ?>
    <div class="card">
        <div class="card-title">
            <i class="fas fa-trash-alt"></i> Delete Contract - Admin Only
        </div>
        
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <span><strong>Warning:</strong> Deleting a contract cannot be undone. Please proceed with caution.</span>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Contract ID</th>
                        <th>Vendor</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $contracts->data_seek(0); while($c=$contracts->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#CT-<?= str_pad($c['Contract_ID'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                        <td><?= htmlspecialchars($c['Vendor_Name']) ?></td>
                        <td><?= date('M d, Y', strtotime($c['Start_Date'])) ?></td>
                        <td><?= date('M d, Y', strtotime($c['End_Date'])) ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirmDelete()" style="margin: 0;">
                                <input type="hidden" name="Contract_ID" value="<?= $c['Contract_ID'] ?>">
                                <button type="submit" name="deleteContract" class="btn-sm btn-danger">
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
function confirmDelete() {
    return confirm('⚠️ Are you sure you want to delete this contract?\nThis action cannot be undone.');
}

// Set minimum date for end date based on start date
const startDate = document.querySelector('input[name="Start_Date"]');
const endDate = document.querySelector('input[name="End_Date"]');

if(startDate && endDate) {
    startDate.addEventListener('change', function() {
        endDate.min = this.value;
    });
}
</script>

</body>
</html>