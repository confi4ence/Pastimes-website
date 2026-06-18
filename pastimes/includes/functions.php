<?php

// includes/functions.php - Reusable helpers


function clean_input($data) {
    return htmlspecialchars(trim($data));
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

function require_login() {
    if (!is_logged_in()) {
        redirect('/pastimes/login.php');
    }
}

function require_admin() {
    if (!is_admin_logged_in()) {
        redirect('/pastimes/admin_login.php');
    }
}

function get_initials($name) {
    $parts = explode(' ', trim($name));
    $initials = '';
    foreach ($parts as $p) {
        $initials .= strtoupper(substr($p, 0, 1));
    }
    return substr($initials, 0, 2);
}
?>