<?php
// delete-news.php - удаление новости
require_once 'auth.php';
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Проверяем авторизацию
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Доступ запрещен']);
    exit;
}

// Проверяем что передан ID новости
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'error' => 'Не указан ID новости']);
    exit;
}

$id = $_POST['id'];

try {
    $conn = getDBConnection();
    
    // Удаляем новость по ID
    $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Новость удалена']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Новость не найдена']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>