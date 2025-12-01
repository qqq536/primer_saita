<?php
// get-news.php - получение новостей из SQLite
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $conn = getDBConnection();
    
    // Получаем все новости, самые свежие первыми
    $stmt = $conn->query("SELECT * FROM news ORDER BY created_at DESC");
    $news = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Форматируем дату и обрабатываем переносы строк
    foreach ($news as &$item) {
        $item['created_at'] = date('d.m.Y H:i', strtotime($item['created_at']));
        
        // ЗАМЕНЯЕМ ПЕРЕНОСЫ СТРОК НА HTML ТЕГИ
        $item['content'] = nl2br(htmlspecialchars($item['content']));
        
        // Добавляем полный путь к медиа-файлу
        if (!empty($item['media_file'])) {
            $item['media_url'] = 'media/' . $item['media_file'];
        }
    }
    
    echo json_encode($news, JSON_UNESCAPED_UNICODE);
    
} catch(PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>