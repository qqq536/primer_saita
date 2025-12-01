<?php
session_start();
$admin_password = 'Phpie©2025';

// Проверяем авторизацию
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Функция входа
function login($password) {
    global $admin_password;
    if ($password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        return true;
    }
    return false;
}

// Функция выхода
function logout() {
    session_destroy();
}
?>