<?php
// admin.php - админка с паролем
require_once 'auth.php';

// Если не авторизован - показываем форму входа
if (!isLoggedIn()) {
    if ($_POST['password'] ?? '') {
        // Проверяем пароль
        if (login($_POST['password'])) {
            // Успешный вход - перезагружаем страницу
            header('Location: admin.php');
            exit;
        } else {
            $error = 'Неверный пароль!';
        }
    }
    
    // Показываем форму входа
    echo '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Вход в админку</title>
        <style>
            body { font-family: Arial; max-width: 400px; margin: 100px auto; padding: 20px; }
            .login-form { background: #f5f5f5; padding: 30px; border-radius: 10px; }
            input[type="password"] { width: 100%; padding: 10px; margin: 10px 0; }
            button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
            .error { color: red; margin-bottom: 10px; }
        </style>
    </head>
    <body>
        <div class="login-form">
            <h2>Вход в админку</h2>
            '. (isset($error) ? '<div class="error">'.$error.'</div>' : '') .'
            <form method="POST">
                <input type="password" name="password" placeholder="Введите пароль" required>
                <button type="submit">Войти</button>
            </form>
            <p><a href="index.html">← На главную</a></p>
        </div>
    </body>
    </html>
    ';
    exit;
}

// Если авторизован - показываем админку
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админка - Mediapolis News</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <h1>Кабинет администратора Mediapolis_news</h1>
             <nav>
            <a href="index.html">Главная</a>
            <a href="admin.php">Кабинет администратора</a>
            <a href="счётчик.html">Счётчик до праздников</a>
            <a href="weather.html">Погода</a>
            <a href="videos.html">Видео</a>
        </nav>
    </header>
        </div>
    </header>

    <main>
        <?php
        // Обработка выхода
        if (isset($_GET['logout'])) {
            logout();
            header('Location: admin.php');
            exit;
        }
        ?>

        <!-- Форма добавления новости -->
        <section class="add-news-form">
            <h2>📝 Добавить новость</h2>
            <form id="news-form" enctype="multipart/form-data">
                <div class="form-group">
                    <input type="text" name="title" placeholder="Заголовок" required>
                </div>
                <div class="form-group">
                    <textarea name="content" placeholder="Текст новости" rows="5" required></textarea>
                </div>
                <div class="form-group">
                    <input type="text" name="author" placeholder="Автор" value="Админ">
                </div>
                
                <!-- Выбор типа медиа -->
                <div class="form-group">
                    <label>📁 Добавить медиа (опционально):</label>
                    <select name="media_type" id="media-type" onchange="toggleMediaInput()">
                        <option value="">Без медиа</option>
                        <option value="image">🖼️ Изображение</option>
                        <option value="video">🎥 Видео</option>
                        <option value="audio">🎵 Аудио</option>
                    </select>
                </div>
                
                <!-- Поле для загрузки файла -->
                <div class="form-group" id="media-file-group" style="display: none;">
                    <input type="file" name="media_file" id="media-file" accept="">
                    <small id="file-help">Выберите файл</small>
                </div>

                <button type="submit" class="btn">📤 Опубликовать</button>
            </form>
            <div id="message"></div>
        </section>

        <!-- Форма добавления видео -->
        <section class="add-news-form" style="margin-top: 3rem;">
            <h2>📹 Добавить видео</h2>
            <form id="video-form" enctype="multipart/form-data">
                <div class="form-group">
                    <input type="text" name="title" placeholder="Название видео" required>
                </div>
                <div class="form-group">
                    <input type="file" name="video_file" accept="video/*" required>
                    <small>Поддерживаемые форматы: MP4, WebM, OGG, MOV (макс. 50MB)</small>
                </div>
                <div class="form-group">
                    <textarea name="description" placeholder="Описание видео (необязательно)" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <input type="text" name="author" placeholder="Автор" value="Админ">
                </div>
                <button type="submit" class="btn">📤 Загрузить видео</button>
            </form>
            <div id="video-message"></div>
        </section>

        <hr>

        <!-- Управление новостями -->
        <section class="news-management">
            <h2>📰 Управление новостями</h2>
            <div id="news-list">
                Загрузка...
            </div>
        </section>

        <!-- Управление видео -->
        <section class="videos-management" style="margin-top: 3rem;">
            <h2>📹 Управление видео</h2>
            <div id="videos-list">
                Загрузка...
            </div>
        </section>
    </main>

    <script>
        // ===== ФУНКЦИИ ДЛЯ НОВОСТЕЙ =====
        
        // Загрузка списка новостей для админки
        function loadNewsForAdmin() {
            fetch('get-news.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP error! status: ' + response.status);
                    }
                    return response.json();
                })
                .then(news => {
                    const container = document.getElementById('news-list');
                    
                    if (news.error) {
                        container.innerHTML = '<div class="error">Ошибка загрузки новостей: ' + news.error + '</div>';
                        return;
                    }
                    
                    if (news.length === 0) {
                        container.innerHTML = '<p>Нет новостей</p>';
                        return;
                    }
                    
                    let html = '';
                    news.forEach(item => {
                        html += `
                            <div class="news-admin-item">
                                <div class="news-admin-header">
                                    <h3>${item.title}</h3>
                                    <button class="delete-btn" onclick="deleteNews(${item.id})">🗑️ Удалить</button>
                                </div>
                                <div class="news-admin-meta">
                                    <span>Автор: ${item.author}</span> | 
                                    <span>Дата: ${item.created_at}</span>
                                </div>
                                <div class="news-admin-content">${item.content}</div>
                                ${item.media_url ? `
                                    <div class="news-admin-media">
                                        Медиа: ${item.media_type} 
                                        <a href="${item.media_url}" target="_blank">[просмотр]</a>
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                })
                .catch(error => {
                    console.error('Ошибка загрузки новостей:', error);
                    document.getElementById('news-list').innerHTML = '<div class="error">Ошибка загрузки новостей</div>';
                });
        }

        // Функция удаления новости
        function deleteNews(id) {
            if (!confirm('Вы уверены что хотите удалить эту новость?')) {
                return;
            }
            
            fetch('delete-news.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + id
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    alert('Новость удалена!');
                    loadNewsForAdmin();
                } else {
                    alert('Ошибка: ' + result.error);
                }
            })
            .catch(error => {
                console.error('Ошибка удаления новости:', error);
                alert('Ошибка при удалении. Проверьте консоль для деталей.');
            });
        }

        // ===== ФУНКЦИИ ДЛЯ ВИДЕО =====

        // Загрузка списка видео для админки
        function loadVideosForAdmin() {
            fetch('get-videos-admin.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP error! status: ' + response.status);
                    }
                    return response.json();
                })
                .then(videos => {
                    const container = document.getElementById('videos-list');
                    
                    if (videos.error) {
                        container.innerHTML = '<div class="error">Ошибка загрузки видео: ' + videos.error + '</div>';
                        return;
                    }
                    
                    if (videos.length === 0) {
                        container.innerHTML = '<p>Нет загруженных видео</p>';
                        return;
                    }
                    
                    let html = '';
                    videos.forEach(video => {
                        html += `
                            <div class="video-admin-item">
                                <div class="video-admin-header">
                                    <h3>${video.title}</h3>
                                    <button class="delete-btn" onclick="deleteVideo(${video.id})">🗑️ Удалить</button>
                                </div>
                                <div class="video-admin-meta">
                                    <span>Автор: ${video.author}</span> | 
                                    <span>Дата: ${video.created_at}</span> |
                                    <span>Файл: ${video.video_file}</span>
                                </div>
                                ${video.description ? `<div class="video-admin-description">${video.description}</div>` : ''}
                                <div class="video-preview">
                                    <video controls width="200">
                                        <source src="${video.video_url}" type="video/mp4">
                                    </video>
                                </div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                })
                .catch(error => {
                    console.error('Ошибка загрузки видео:', error);
                    document.getElementById('videos-list').innerHTML = '<div class="error">Ошибка загрузки видео</div>';
                });
        }

        // Функция удаления видео
        // Функция удаления видео с детальной отладкой
function deleteVideo(id) {
    console.log('🔄 Попытка удалить видео с ID:', id);
    
    if (!confirm('Вы уверены что хотите удалить это видео? Файл будет удален с сервера.')) {
        console.log('❌ Удаление отменено пользователем');
        return;
    }
    
    console.log('📤 Отправка запроса на удаление...');
    
    fetch('delete-video.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + id
    })
    .then(response => {
        console.log('📥 Получен ответ:', response.status, response.statusText);
        
        if (!response.ok) {
            console.error('❌ HTTP ошибка:', response.status, response.statusText);
            throw new Error('HTTP error! status: ' + response.status);
        }
        
        return response.text().then(text => {
            console.log('📄 Ответ сервера:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('❌ Ошибка парсинга JSON:', e);
                console.error('📄 Ответ был:', text);
                throw new Error('Invalid JSON response: ' + text.substring(0, 100));
            }
        });
    })
    .then(result => {
        console.log('✅ JSON ответ:', result);
        
        if (result.success) {
            console.log('🎉 Видео успешно удалено');
            alert('Видео удалено!');
            loadVideosForAdmin();
        } else {
            console.error('❌ Ошибка от сервера:', result.error);
            alert('Ошибка: ' + result.error);
        }
    })
    .catch(error => {
        console.error('💥 Критическая ошибка:', error);
        alert('Ошибка при удалении: ' + error.message + '\n\nПроверьте консоль для деталей.');
    });
}
        // ===== ФУНКЦИИ ДЛЯ ФОРМ =====

        // Управление полем загрузки файла для новостей
        function toggleMediaInput() {
            const mediaType = document.getElementById('media-type').value;
            const fileGroup = document.getElementById('media-file-group');
            const fileInput = document.getElementById('media-file');
            const fileHelp = document.getElementById('file-help');
            
            if (mediaType) {
                fileGroup.style.display = 'block';
                
                // Устанавливаем допустимые типы файлов
                switch(mediaType) {
                    case 'image':
                        fileInput.accept = 'image/*';
                        fileHelp.textContent = 'Поддерживаются: JPG, PNG, GIF (макс. 5MB)';
                        break;
                    case 'video':
                        fileInput.accept = 'video/*';
                        fileHelp.textContent = 'Поддерживаются: MP4, AVI, MOV (макс. 20MB)';
                        break;
                    case 'audio':
                        fileInput.accept = 'audio/*';
                        fileHelp.textContent = 'Поддерживаются: MP3, WAV (макс. 10MB)';
                        break;
                }
            } else {
                fileGroup.style.display = 'none';
                fileInput.value = '';
            }
        }

        // Обработка формы новостей
        document.getElementById('news-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('add-news.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                document.getElementById('message').innerHTML = result;
                if (result.includes('успешно')) {
                    this.reset();
                    document.getElementById('media-file-group').style.display = 'none';
                    loadNewsForAdmin();
                }
            })
            .catch(error => {
                console.error('Ошибка добавления новости:', error);
                document.getElementById('message').innerHTML = '<div class="error">Ошибка: ' + error + '</div>';
            });
        });

        // Обработка формы видео
        document.getElementById('video-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Проверяем что файл выбран
            const fileInput = this.querySelector('input[type="file"]');
            if (!fileInput.files.length) {
                document.getElementById('video-message').innerHTML = '<div class="error">Выберите видео файл!</div>';
                return;
            }
            
            fetch('add-video.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                document.getElementById('video-message').innerHTML = result;
                if (result.includes('успешно')) {
                    this.reset();
                    loadVideosForAdmin();
                }
            })
            .catch(error => {
                console.error('Ошибка добавления видео:', error);
                document.getElementById('video-message').innerHTML = '<div class="error">Ошибка: ' + error + '</div>';
            });
        });

        // ===== ЗАГРУЗКА ПРИ СТАРТЕ =====

        // Загружаем данные при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            loadNewsForAdmin();
            loadVideosForAdmin();
        });
    </script>

    <style>
        /* Стили для админки */
        .news-admin-item, .video-admin-item {
            background: var(--bg-secondary);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin: 1rem 0;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .news-admin-header, .video-admin-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .news-admin-header h3, .video-admin-header h3 {
            color: var(--text-primary);
            margin: 0;
            flex: 1;
        }

        .news-admin-meta, .video-admin-meta {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .news-admin-content, .video-admin-description {
            color: var(--text-secondary);
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .news-admin-media {
            color: var(--text-muted);
            font-size: 0.9rem;
            padding: 0.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--radius-small);
        }

        .video-preview video {
            border-radius: var(--radius-small);
            background: #000;
            max-width: 100%;
        }

        .delete-btn {
            background: #ff4757;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 10px;
        }

        .delete-btn:hover {
            background: #ff3742;
        }

        hr {
            border: none;
            border-top: 2px solid rgba(255, 255, 255, 0.1);
            margin: 2rem 0;
        }
    </style>
</body>
</html>