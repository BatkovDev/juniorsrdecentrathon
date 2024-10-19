<?php
// Токен вашего бота
$botToken = "7512014253:AAGxFc6pTbUstd6RR5vXj760-YVDGRszUTk";

// API URL для запросов к Telegram
$apiURL = "https://api.telegram.org/bot$botToken/";

// Получаем входящий запрос от Telegram (webhook)
$content = file_get_contents("php://input");
$update = json_decode($content, true);

// Проверяем, что запрос пришел
if (!$update || !isset($update['message'])) {
    exit;
}

$chatId = $update['message']['chat']['id'];
$messageText = $update['message']['text'];
$firstName = isset($update['message']['from']['first_name']) ? $update['message']['from']['first_name'] : '';  // Имя пользователя
$telegramId = $update['message']['from']['id'];  // Telegram ID пользователя

// Если пользователь отправил команду /start
if ($messageText == "/start") {
    // Создаем URL с данными пользователя
    $url = "https://brold.ru/index.php?telegram_id=$telegramId&first_name=" . urlencode($firstName);

    // Создаем кнопку с Mini App
    $keyboard = [
        'inline_keyboard' => [
            [
                [
                    'text' => 'Открыть обучение',
                    'web_app' => [
                        'url' => $url // URL мини-приложения с параметрами
                    ]
                ]
            ]
        ]
    ];

    // Массив данных для отправки сообщения
    $data = [
        'chat_id' => $chatId,
        'text' => "Добро пожаловать! Для прохождения обучения нажмите ниже",
        'reply_markup' => json_encode($keyboard)
    ];

    // Инициализируем cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiURL . "sendMessage");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    // Отправляем запрос и получаем ответ
    $response = curl_exec($ch);

    // Проверка на ошибки cURL
    if (curl_errno($ch)) {
        error_log('Ошибка cURL: ' . curl_error($ch));
    } else {
        // Логируем успешный ответ
        error_log('Ответ Telegram: ' . $response);
    }

    // Закрываем cURL
    curl_close($ch);
}
?>
