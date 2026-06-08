<?php
// admin/auth.php
require_once __DIR__ . '/../config.php';

function check_admin_auth() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}
?>