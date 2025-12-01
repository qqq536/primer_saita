<?php
// config.php - настройки для SQLite
$db_file = __DIR__ . '/news.db';

function getDBConnection() {
    global $db_file;
    try {
        $conn = new PDO("sqlite:" . $db_file);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->exec("PRAGMA foreign_keys = ON");
        return $conn;
    } catch(PDOException $e) {
        die("Ошибка подключения к базе: " . $e->getMessage());
    }
}
// Автоматически создаем таблицу для видео
try {
    $conn = getDBConnection();
    $conn->exec("CREATE TABLE IF NOT EXISTS videos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        video_url TEXT NOT NULL,
        description TEXT,
        author TEXT DEFAULT 'Админ',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
} catch(PDOException $e) {
    // Игнорируем ошибку если таблица уже существует
}

// ОБНОВЛЕНИЕ СТРУКТУРЫ ТАБЛИЦЫ
try {
    $conn = getDBConnection();
    
    // Проверяем существует ли таблица
    $tableExists = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='news'")->fetch();
    
    if (!$tableExists) {
        // Создаем таблицу если ее нет
        $conn->exec("CREATE TABLE news (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            author TEXT DEFAULT 'Админ',
            media_type TEXT,
            media_file TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    } else {
        // Добавляем недостающие колонки если таблица есть
        $result = $conn->query("PRAGMA table_info(news)");
        $columns = $result->fetchAll(PDO::FETCH_COLUMN, 1);
        
        if (!in_array('media_type', $columns)) {
            $conn->exec("ALTER TABLE news ADD COLUMN media_type TEXT");
        }
        
        if (!in_array('media_file', $columns)) {
            $conn->exec("ALTER TABLE news ADD COLUMN media_file TEXT");
        }
    }
    
} catch(PDOException $e) {
    // Игнорируем ошибки при обновлении структуры
}

// Создаем папку для медиа-файлов
$media_dir = __DIR__ . '/media/';
if (!is_dir($media_dir)) {
    mkdir($media_dir, 0777, true);
}


// В config.php обнови создание таблицы видео
try {
    $conn = getDBConnection();
    $conn->exec("CREATE TABLE IF NOT EXISTS videos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        video_file TEXT NOT NULL,  // Имя файла видео
        description TEXT,
        author TEXT DEFAULT 'Админ',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
} catch(PDOException $e) {
    // Игнорируем ошибку если таблица уже существует
}
// В config.php после создания таблицы видео добавь:
try {
    $conn = getDBConnection();
    
    // Проверяем существует ли таблица videos
    $tableExists = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='videos'")->fetch();
    
    if ($tableExists) {
        // Проверяем существующие колонки
        $result = $conn->query("PRAGMA table_info(videos)");
        $columns = $result->fetchAll(PDO::FETCH_COLUMN, 1);
        
        // Добавляем video_file если нет
        if (!in_array('video_file', $columns)) {
            $conn->exec("ALTER TABLE videos ADD COLUMN video_file TEXT");
            echo "<!-- Таблица videos обновлена -->";
        }
        
        // Удаляем старую колонку video_url если есть
        if (in_array('video_url', $columns)) {
            $conn->exec("ALTER TABLE videos DROP COLUMN video_url");
        }
    } else {
        // Создаем таблицу если её нет
        $conn->exec("CREATE TABLE videos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            video_file TEXT NOT NULL,
            description TEXT,
            author TEXT DEFAULT 'Админ',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        echo "<!-- Таблица videos создана -->";
    }
    
} catch(PDOException $e) {
    // Игнорируем ошибки при обновлении структуры
}

// Создаем папку для видео
$videos_dir = __DIR__ . '/videos/';
if (!is_dir($videos_dir)) {
    mkdir($videos_dir, 0777, true);
}
?>
        
        