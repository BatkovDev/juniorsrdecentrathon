<?php

// Ваш API-ключ Google Gemini
$apiKey = 'AIzaSyDltcERIhqdcq5aWuU0m1nzQ6VJI6FXIMo'; // Замените на ваш API ключ

// Функция для отправки запроса на Google Gemini API
function generateContentWithGemini($prompt) {
    global $apiKey;

    // URL для запроса к Google Gemini API
    $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' . $apiKey;

    // Данные запроса
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ]
    ];

    // Настройка cURL для отправки POST-запроса
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    // Получаем ответ
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return 'Ошибка при подключении к API.';
    }

    // Декодируем JSON-ответ
    $responseData = json_decode($response, true);

    // Отладочная информация
    error_log('HTTP Code: ' . $httpCode);
    error_log('Ответ от API: ' . $response);

    // Проверка на наличие ошибок в ответе
    if (isset($responseData['error'])) {
        return 'Ошибка API: ' . $responseData['error']['message'];
    }

    // Извлечение сгенерированного текста
    if (!empty($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        return $responseData['candidates'][0]['content']['parts'][0]['text'];
    } else {
        return 'Ответ пустой. Данные от API: ' . print_r($responseData, true);
    }
}

// Обработка формы отправки запроса
$statusMessage = "Введите запрос для генерации контента.";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $submittedPrompt = htmlspecialchars($_POST['prompt']);

    // Проверяем, был ли передан запрос
    if (empty($submittedPrompt)) {
        $statusMessage = 'Запрос не передан.';
    } else {
        // Отправляем запрос на API Google Gemini
        $statusMessage = generateContentWithGemini($submittedPrompt);
    }
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <title>Генерация контента с помощью Gemini</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f0f0;
            color: #333;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: 100vh;
        }
        .container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            width: 80%;
            max-width: 600px;
            text-align: center;
        }
        .submission-area {
            margin: 20px 0;
        }
        textarea {
            width: 100%;
            height: 200px;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 10px;
            font-size: 1em;
            resize: none;
        }
        .status {
            margin-top: 20px;
            font-weight: bold;
            color: #555;
        }
        .submit-btn {
            background: linear-gradient(45deg, #ff6f91, #ff9a44);
            color: #ffffff;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            margin-top: 10px;
            transition: background 0.3s ease;
        }
        .submit-btn:hover {
            background: linear-gradient(45deg, #ff9a44, #ff6f91);
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Генерация контента с помощью Gemini</h2>
        <form method="POST" action="">
            <div class="submission-area">
                <textarea name="prompt" placeholder="Введите ваш запрос..." required></textarea>
            </div>
            <button type="submit" class="submit-btn">Отправить запрос</button>
        </form>
        <div class="status">Статус: <span class="status-indicator"><?= $statusMessage; ?></span></div>
    </div>
</body>
</html>
