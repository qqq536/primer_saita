<?php
// add-news.php - добавление новости в SQLite
require_once 'auth.php';
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

// Проверяем авторизацию
if (!isLoggedIn()) {
    die("<div class='error'>Доступ запрещен</div>");
}

// Остальной код без изменений...
$title = $_POST['title'] ?? '';
$content = $_POST['content'] ?? '';
$author = $_POST['author'] ?? 'Админ';
$media_type = $_POST['media_type'] ?? '';

if (empty($title) || empty($content)) {
    die("<div class='error'>Заполните все обязательные поля!</div>");
}

$media_file = null;

// Обработка загрузки файла
if ($media_type && isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['media_file'];
    
    // Проверка размера файла
    $max_sizes = [
        'image' => 5 * 1024 * 1024, // 5MB
        'video' => 20 * 1024 * 1024, // 20MB
        'audio' => 10 * 1024 * 1024 // 10MB
    ];
    
    if ($file['size'] > $max_sizes[$media_type]) {
        die("<div class='error'>Файл слишком большой!</div>");
    }
    
    // Генерируем уникальное имя файла
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $media_file = uniqid() . '.' . $extension;
    $upload_path = __DIR__ . '/media/' . $media_file;
    
    // Перемещаем файл
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        die("<div class='error'>Ошибка загрузки файла!</div>");
    }
}

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO news (title, content, author, media_type, media_file) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$title, $content, $author, $media_type, $media_file]);
    echo "<div class='success'>Новость успешно добавлена!" . ($media_file ? " Медиа-файл загружен." : "") . "</div>";
} catch(PDOException $e) {
    echo "<div class='error'>Ошибка: " . $e->getMessage() . "</div>";
}
?>