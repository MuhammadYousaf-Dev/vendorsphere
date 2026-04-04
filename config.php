<?php
// config.php - Simple Role Permissions

// Define which pages each role can access
$role_pages = [
    'admin' => ['dashboard.php', 'vendors.php', 'contracts.php', 'departments.php', 
                'purchase_orders.php', 'evaluation.php', 'users.php', 'settings.php'],
    'staff' => ['dashboard.php', 'vendors.php', 'contracts.php', 'departments.php', 
                'purchase_orders.php', 'evaluation.php'],
    'user' => ['dashboard.php', 'vendors.php', 'contracts.php']
];

// Check if role can access a page
function canAccess($page, $role) {
    global $role_pages;
    return in_array($page, $role_pages[$role] ?? []);
}

// Check specific permissions
function hasPermission($role, $action = 'view') {
    $permissions = [
        'admin' => ['view', 'create', 'edit', 'delete'],
        'staff' => ['view', 'create', 'edit'],
        'user' => ['view']
    ];
    
    return in_array($action, $permissions[$role] ?? ['view']);
}

// Get accessible pages for a role
function getAccessiblePages($role) {
    global $role_pages;
    return $role_pages[$role] ?? ['dashboard.php'];
}
?>