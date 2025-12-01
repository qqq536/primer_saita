<?php
// add-video.php - загрузка видео с устройства (с отладкой)
require_once 'auth.php';
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

// Включаем вывод всех ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isLoggedIn()) {
    die("<div class='error'>Доступ запрещен</div>");
}

echo "<!-- Debug: Скрипт запущен -->";

$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$author = $_POST['author'] ?? 'Админ';

echo "<!-- Debug: title = $title -->";
echo "<!-- Debug: files = " . print_r($_FILES, true) . " -->";

if (empty($title)) {
    die("<div class='error'>Заполните заголовок!</div>");
}

if (!isset($_FILES['video_file']) || $_FILES['video_file']['error'] === UPLOAD_ERR_NO_FILE) {
    die("<div class='error'>Выберите видео файл!</div>");
}

// Обработка загрузки видео
$file = $_FILES['video_file'];
echo "<!-- Debug: file error = " . $file['error'] . " -->";

if ($file['error'] === UPLOAD_ERR_OK) {
    echo "<!-- Debug: файл получен успешно -->";
    
    // Проверяем тип файла
    $allowed_types = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-msvideo'];
    $file_type = $file['type'];
    echo "<!-- Debug: file type = $file_type -->";
    
    if (!in_array($file_type, $allowed_types)) {
        die("<div class='error'>Разрешены только MP4, WebM, OGG, MOV, AVI файлы. Ваш тип: $file_type</div>");
    }
    
    // Проверяем размер (макс. 50MB)
    $max_size = 50 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        $size_mb = round($file['size'] / (1024 * 1024), 2);
        die("<div class='error'>Файл слишком большой ($size_mb MB). Максимум: 50MB</div>");
    }
    
    // Создаем папку videos если её нет
    $videos_dir = __DIR__ . '/videos/';
    if (!is_dir($videos_dir)) {
        mkdir($videos_dir, 0777, true);
        echo "<!-- Debug: папка videos создана -->";
    }
    
    // Проверяем права на запись
    if (!is_writable($videos_dir)) {
        die("<div class='error'>Нет прав на запись в папку videos</div>");
    }
    
    // Генерируем уникальное имя файла
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $video_file = uniqid() . '.' . $extension;
    $upload_path = $videos_dir . $video_file;
    
    echo "<!-- Debug: upload path = $upload_path -->";
    
    // Перемещаем файл
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        echo "<!-- Debug: файл перемещен успешно -->";
        
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("INSERT INTO videos (title, video_file, description, author) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $video_file, $description, $author]);
            echo "<div class='success'>✅ Видео успешно загружено! Файл: $video_file</div>";
        } catch(PDOException $e) {
            // Удаляем файл если ошибка БД
            unlink($upload_path);
            echo "<div class='error'>❌ Ошибка базы данных: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='error'>❌ Ошибка перемещения файла. Проверьте права доступа.</div>";
    }
} else {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE => 'Файл слишком большой',
        UPLOAD_ERR_FORM_SIZE => 'Файл слишком большой',
        UPLOAD_ERR_PARTIAL => 'Файл загружен частично',
        UPLOAD_ERR_NO_FILE => 'Файл не выбран',
        UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка',
        UPLOAD_ERR_CANT_WRITE => 'Ошибка записи на диск',
        UPLOAD_ERR_EXTENSION => 'Расширение PHP остановило загрузку'
    ];
    
    $error_msg = $error_messages[$file['error']] ?? 'Неизвестная ошибка';
    echo "<div class='error'>❌ Ошибка загрузки: $error_msg (код: {$file['error']})</div>";
}
?>