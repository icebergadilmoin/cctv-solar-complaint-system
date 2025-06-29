<?php
session_start();
require 'includes/config.php';

// Redirect to appropriate dashboard based on role
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: admin/dashboard.php');
            break;
        case 'manager':
            header('Location: manager/dashboard.php');
            break;
        case 'worker':
            header('Location: worker/dashboard.php');
            break;
        case 'client':
            header('Location: client/dashboard.php');
            break;
        default:
            header('Location: login.php');
    }
    exit();
} else {
    header('Location: login.php');
    exit();
}
?>