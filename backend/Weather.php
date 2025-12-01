<?php
header('Content-Type: application/json; charset=utf-8');

// Координаты Перми (фиксированные)
$lat = 58.0105;
$lon = 56.2502;
$city_name = "Пермь";

try {
    // Получаем данные погоды для Перми
    $weather_url = "https://api.open-meteo.com/v1/forecast?" . http_build_query([
        'latitude' => $lat,
        'longitude' => $lon,
        'current' => 'temperature_2m,relative_humidity_2m,apparent_temperature,wind_speed_10m,weather_code',
        'timezone' => 'auto',
        'forecast_days' => 1
    ]);
    
    $weather_data = json_decode(file_get_contents($weather_url), true);
    
    if (!$weather_data || !isset($weather_data['current'])) {
        throw new Exception('Ошибка получения данных погоды');
    }
    
    // Функция для описания погоды
    function getWeatherDescription($code) {
        $descriptions = [
            0 => 'Ясно',
            1 => 'Преимущественно ясно', 
            2 => 'Переменная облачность',
            3 => 'Пасмурно',
            45 => 'Туман',
            48 => 'Туман с инеем',
            51 => 'Лёгкая морось',
            53 => 'Умеренная морось',
            55 => 'Сильная морось',
            61 => 'Небольшой дождь',
            63 => 'Умеренный дождь', 
            65 => 'Сильный дождь',
            71 => 'Небольшой снег',
            73 => 'Умеренный снег',
            75 => 'Сильный снег',
            80 => 'Небольшие ливни',
            81 => 'Умеренные ливни', 
            82 => 'Сильные ливни',
            85 => 'Небольшие снегопады',
            86 => 'Сильные снегопады',
            95 => 'Гроза',
            96 => 'Гроза с градом',
            99 => 'Сильная гроза с градом'
        ];
        return $descriptions[$code] ?? 'Облачно';
    }
    
    // Функция для иконок погоды
    function getWeatherIcon($code) {
        $icons = [
            0 => '☀️', 1 => '☀️', 2 => '⛅', 3 => '☁️',
            45 => '🌫️', 48 => '🌫️', 
            51 => '🌧️', 53 => '🌧️', 55 => '🌧️',
            61 => '🌧️', 63 => '🌧️', 65 => '🌧️',
            71 => '❄️', 73 => '❄️', 75 => '❄️',
            80 => '🌧️', 81 => '🌧️', 82 => '🌧️',
            85 => '❄️', 86 => '❄️',
            95 => '⛈️', 96 => '⛈️', 99 => '⛈️'
        ];
        return $icons[$code] ?? '🌤️';
    }
    
    $response = [
        'success' => true,
        'city' => $city_name,
        'temp' => round($weather_data['current']['temperature_2m']),
        'feels_like' => round($weather_data['current']['apparent_temperature']),
        'description' => getWeatherDescription($weather_data['current']['weather_code']),
        'icon' => getWeatherIcon($weather_data['current']['weather_code']),
        'humidity' => $weather_data['current']['relative_humidity_2m'],
        'wind' => round($weather_data['current']['wind_speed_10m'], 1)
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>