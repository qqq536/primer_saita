<?php
// get-videos.php - получение списка видео
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $conn = getDBConnection();
    $stmt = $conn->query("SELECT * FROM videos ORDER BY created_at DESC");
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($videos as &$video) {
        $video['created_at'] = date('d.m.Y H:i', strtotime($video['created_at']));
        $video['video_url'] = 'videos/' . $video['video_file'];
    }
    
    echo json_encode($videos, JSON_UNESCAPED_UNICODE);
    
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>