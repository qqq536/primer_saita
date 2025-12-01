<?php
// delete-video.php - удаление видео
require_once 'auth.php';
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Проверяем авторизацию
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Доступ запрещен']);
    exit;
}

// Проверяем что передан ID видео
if (!isset($_POST['id']) || empty($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Не указан ID видео']);
    exit;
}

$id = $_POST['id'];

try {
    $conn = getDBConnection();
    
    // Сначала получаем информацию о видео чтобы удалить файл
    $stmt = $conn->prepare("SELECT video_file FROM videos WHERE id = ?");
    $stmt->execute([$id]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$video) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Видео не найдено']);
        exit;
    }
    
    // Удаляем видео из базы данных
    $stmt = $conn->prepare("DELETE FROM videos WHERE id = ?");
    $stmt->execute([$id]);
    
    // Удаляем файл видео с сервера
    $video_file = __DIR__ . '/videos/' . $video['video_file'];
    if (file_exists($video_file)) {
        unlink($video_file);
    }
    
    echo json_encode(['success' => true, 'message' => 'Видео удалено']);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>