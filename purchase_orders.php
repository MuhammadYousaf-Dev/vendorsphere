<?php
include 'auth.php';
include 'db.php';

/* =========================
   SAFE ROLE HANDLING
========================= */
$role_lower = strtolower($_SESSION['role'] ?? '');
$action = $_GET['action'] ?? '';
$msg = '';

$can_add    = in_array($role_lower, ['admin','staff']);
$can_edit   = in_array($role_lower, ['admin','staff']);
$can_delete = $role_lower === 'admin';
$can_view   = in_array($role_lower, ['admin','staff','user']);

if (!$can_view && !$can_add && !$can_edit && !$can_delete) {
    header("Location: dashboard.php");
    exit();
}

/* =========================
   ADD PURCHASE ORDER
========================= */
if (isset($_POST['addPO']) && $can_add) {
    $vendor = intval($_POST['Vendor_ID']);
    $date   = $_POST['Order_Date'];
    $amount = floatval($_POST['Total_Amount']);
    $status = $_POST['Order_Status'];

    $stmt = $conn->prepare("
        INSERT INTO purchaseorder 
        (Vendor_ID, Order_Date, Total_Amount, Order_Status)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isds", $vendor, $date, $amount, $status);
    $stmt->execute();

    $msg = "✅ Purchase Order added successfully!";
}

/* =========================
   UPDATE PURCHASE ORDER
========================= */
if (isset($_POST['updatePO']) && $can_edit) {
    $id     = intval($_POST['PO_ID']);
    $date   = $_POST['Order_Date'];
    $amount = floatval($_POST['Total_Amount']);
    $status = $_POST['Order_Status'];

    $stmt = $conn->prepare("
        UPDATE purchaseorder 
        SET Order_Date=?, Total_Amount=?, Order_Status=? 
        WHERE PO_ID=?
    ");
    $stmt->bind_param("sdsi", $date, $amount, $status, $id);
    $stmt->execute();

    $msg = "✅ Purchase Order updated successfully!";
}

/* =========================
   DELETE PURCHASE ORDER
========================= */
if (isset($_POST['deletePO']) && $can_delete) {
    $id = intval($_POST['PO_ID']);

    $stmt = $conn->prepare("DELETE FROM purchaseorder WHERE PO_ID=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $msg = "✅ Purchase Order deleted successfully!";
}

/* =========================
   FETCH DATA
========================= */
$poList = $conn->query("
    SELECT po.POID, po.VendorID, v.VendorName, PODate,
           po.TotalAmount, Item
    FROM purchaseorder po
    JOIN vendor v ON po.VendorID = v.VendorID
    ORDER BY PODate DESC
");

/* EDIT FETCH */
$editPO = null;
if (isset($_GET['edit']) && $can_edit) {
    $editId = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM purchaseorder WHERE PO_ID=$editId");
    $editPO = $res->fetch_assoc();
}

/* VENDORS LIST */
$vendorsList = $conn->query("SELECT VendorID, VendorName FROM vendor");

/* STATS */
$totalPO = $poList->num_rows;

$totalAmount = $conn->query("
    SELECT IFNULL(SUM(TotalAmount),0) AS total 
    FROM purchaseorder
")->fetch_assoc()['total'];

$pendingPO = $conn->query("
    SELECT COUNT(*) AS count 
    FROM purchaseorder 
    WHERE Item='Pending'
")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <style>
        /* --- Basic Styling --- */
        body { font-family: 'Poppins', sans-serif; margin:0; padding:0; background:#f1f5f9; }
        .container { max-width:1200px; margin:auto; padding:20px; }
        h1 { margin-bottom:20px; color:#2b2d42; }
        table { width:100%; border-collapse: collapse; margin-bottom:30px; }
        th, td { padding:12px 15px; border:1px solid #ddd; text-align:left; }
        th { background:#4361ee; color:white; }
        .btn { padding:8px 15px; border:none; border-radius:5px; cursor:pointer; text-decoration:none; color:white; }
        .btn-primary { background:#4361ee; }
        .btn-warning { background:#f59e0b; }
        .btn-danger { background:#ef4444; }
        .btn-success { background:#4ade80; }
        .alert { padding:15px; margin-bottom:20px; border-radius:5px; }
        .alert-success { background:#dcfce7; color:#166534; }
        .alert-danger { background:#fee2e2; color:#991b1b; }
        .form-group { margin-bottom:15px; }
        input, select { width:100%; padding:10px; border-radius:5px; border:1px solid #ccc; }
        .stats { display:flex; gap:20px; margin-bottom:20px; }
        .stat-card { flex:1; padding:20px; border-radius:10px; background:white; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,0.1);}
    </style>
</head>
<body>
<div class="container">
    <h1>Purchase Orders</h1>

    <!-- Alert Messages -->
    <?php if($msg): ?>
        <div class="alert <?= strpos($msg,'❌')!==false?'alert-danger':'alert-success' ?>">
            <?= $msg ?>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats">
        <div class="stat-card">
            <h3>Total Orders</h3>
            <p><?= $totalPO ?></p>
        </div>
        <div class="stat-card">
            <h3>Total Amount</h3>
            <p>$<?= number_format($totalAmount,2) ?></p>
        </div>
        <div class="stat-card">
            <h3>Pending Orders</h3>
            <p><?= $pendingPO ?></p>
        </div>
    </div>

    <!-- Action Buttons -->
    <?php if($can_add): ?>
        <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Order</a>
    <?php endif; ?>
    <?php if($can_view): ?>
        <a href="?action=read" class="btn btn-success"><i class="fas fa-eye"></i> View Orders</a>
    <?php endif; ?>

    <!-- ADD ORDER FORM -->
    <?php if($action==='add' && $can_add): ?>
        <h2>Add Purchase Order</h2>
        <form method="POST">
            <div class="form-group">
                <label>Vendor</label>
                <select name="Vendor_ID" required>
                    <option value="">--Select Vendor--</option>
                    <?php while($v=$vendorsList->fetch_assoc()): ?>
                        <option value="<?= $v['VendorID'] ?>"><?= $v['VendorName'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Order Date</label>
                <input type="date" name="Order_Date" required>
            </div>
            <div class="form-group">
                <label>Total Amount</label>
                <input type="number" step="0.01" name="Total_Amount" required>
            </div>
            <div class="form-group">
                <label>Order Status</label>
                <select name="Order_Status">
                    <option value="Pending">Pending</option>
                    <option value="Approved">Approved</option>
                    <option value="Delivered">Delivered</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>
            <button type="submit" name="addPO" class="btn btn-primary">Create Order</button>
        </form>
    <?php endif; ?>

    <!-- VIEW ORDERS -->
    <?php if($action==='read' && $can_view): ?>
        <h2>All Purchase Orders</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Vendor</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <?php if($can_edit || $can_delete): ?><th>Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php $poList->data_seek(0); while($po=$poList->fetch_assoc()): ?>
                <tr>
                    <td><?= $po['POID'] ?></td>
                    <td><?= $po['VendorName'] ?></td>
                    <td><?= $po['PODate'] ?></td>
                    <td>$<?= number_format($po['TotalAmount'],2) ?></td>
                    <td><?= $po['Item'] ?></td>
                    <?php if($can_edit || $can_delete): ?>
                    <td>
                        <?php if($can_edit): ?>
                            <a href="?action=update&edit=<?= $po['POID'] ?>" class="btn btn-warning">Edit</a>
                        <?php endif; ?>
                        <?php if($can_delete): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?')">
                                <input type="hidden" name="POID" value="<?= $po['POID'] ?>">
                                <button type="submit" name="deletePO" class="btn btn-danger">Delete</button>
                            </form>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- EDIT ORDER -->
    <?php if($action==='update' && $can_edit && $editPO): ?>
        <h2>Edit Purchase Order #<?= $editPO['PO_ID'] ?></h2>
        <form method="POST">
            <input type="hidden" name="PO_ID" value="<?= $editPO['PO_ID'] ?>">
            <div class="form-group">
                <label>Order Date</label>
                <input type="date" name="Order_Date" value="<?= $editPO['Order_Date'] ?>" required>
            </div>
            <div class="form-group">
                <label>Total Amount</label>
                <input type="number" step="0.01" name="Total_Amount" value="<?= $editPO['Total_Amount'] ?>" required>
            </div>
            <div class="form-group">
                <label>Order Status</label>
                <select name="Order_Status">
                    <option value="Pending" <?= $editPO['Order_Status']=='Pending'?'selected':'' ?>>Pending</option>
                    <option value="Approved" <?= $editPO['Order_Status']=='Approved'?'selected':'' ?>>Approved</option>
                    <option value="Delivered" <?= $editPO['Order_Status']=='Delivered'?'selected':'' ?>>Delivered</option>
                    <option value="Cancelled" <?= $editPO['Order_Status']=='Cancelled'?'selected':'' ?>>Cancelled</option>
                </select>
            </div>
            <button type="submit" name="updatePO" class="btn btn-warning">Update Order</button>
        </form>
    <?php endif; ?>

</div>
</body>
</html>
