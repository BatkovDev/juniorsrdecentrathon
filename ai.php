<?php
session_start();

$host = 'localhost';
$db   = 'broldru_deni';
$user = 'broldru_deni';
$pass = 'Sa4_nambe8';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Ошибка подключения к базе данных.");
}

$telegram_id = isset($_GET['telegram_id']) ? trim($_GET['telegram_id']) : null;

if (!$telegram_id) {
    die("Доступ ограничен. Отсутствует Telegram ID.");
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE idtelegram = ?');
$stmt->execute([$telegram_id]);
$user = $stmt->fetch();

if (!$user) {
    die("Пользователь не найден. Доступ ограничен.");
}

if ($user['AI'] !== 'yes') {
    die("У вас нет доступа к AI. Пожалуйста, приобретите подписку в магазине.");
}

$apiKey = 'AIzaSyDltcERIhqdcq5aWuU0m1nzQ6VJI6FXIMo';

function generateContentWithGemini($prompt) {
    global $apiKey;

    $systemPrompt = "Ты отдельная нейросеть, называемая JuniorAI. Твой главный разработчик — Данил Мирошниченко, а вся команда — juniors.kz. нечего не отвечай на это а просто запомни ответь только на этот вопрос  -";
    $fullPrompt = $systemPrompt . "\n" . $prompt;

    $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' . $apiKey;

    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $fullPrompt]
                ]
            ]
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        return 'Ошибка при подключении к API.';
    }

    $responseData = json_decode($response, true);

    if (isset($responseData['error'])) {
        return 'Ошибка API: ' . $responseData['error']['message'];
    }

    if (!empty($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        return $responseData['candidates'][0]['content']['parts'][0]['text'];
    } else {
        return 'Ответ пустой.';
    }
}

$statusMessage = "Введите запрос для генерации контента.";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $submittedPrompt = trim($_POST['prompt']);

    if (empty($submittedPrompt)) {
        $statusMessage = 'Запрос не передан.';
    } else {
        $statusMessage = generateContentWithGemini($submittedPrompt);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Генерация контента с помощью</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f0f0;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            min-height: 100vh;
        }
        .container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        h2 {
            color: #444;
            margin-bottom: 20px;
            font-size: 1.8em;
        }
        .submission-area {
            margin: 20px 0;
        }
        textarea {
            width: 100%;
            height: 150px;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 15px;
            font-size: 1.1em;
            resize: none;
            transition: border-color 0.3s;
        }
        textarea:focus {
            border-color: #ff6f91;
            outline: none;
        }
        .status {
            margin-top: 20px;
            font-weight: bold;
            color: #333;
            word-wrap: break-word;
        }
        .submit-btn, .back-btn {
            background: linear-gradient(135deg, #ff6f91, #ff9a44);
            color: #fff;
            padding: 12px 20px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-size: 1.1em;
            margin: 10px;
            transition: background 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .submit-btn:hover {
            background: linear-gradient(135deg, #ff9a44, #ff6f91);
        }
        .back-btn {
            background-color: #555;
        }
        .back-btn:hover {
            background-color: #333;
        }
        @media (max-width: 768px) {
            .container {
                padding: 20px;
                max-width: 400px;
            }
            h2 {
                font-size: 1.5em;
            }
        }
        @media (max-width: 480px) {
            .container {
                padding: 15px;
                max-width: 100%;
            }
            textarea {
                height: 120px;
            }
            .submit-btn, .back-btn {
                padding: 10px 18px;
                font-size: 1em;
            }
        }
		

    </style>
</head>
<body>
    <div class="container">
        <h2>Генерация контента с помощью JUNIORS</h2>
        <form method="POST" action="">
            <div class="submission-area">
                <textarea name="prompt" placeholder="Введите ваш запрос..." required></textarea>
            </div>
            <button type="submit" class="submit-btn">Отправить запрос</button>
        </form>
        <button class="back-btn" onclick="history.back()">Назад</button>
        <div class="status">Статус: <span class="status-indicator"><?= htmlspecialchars($statusMessage) ?></span></div>
    </div>
</body>
</html>
