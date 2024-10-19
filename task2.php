<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

$apiKey = 'AIzaSyDltcERIhqdcq5aWuU0m1nzQ6VJI6FXIMo';

function generateContentWithGemini($prompt) {
    global $apiKey;
    $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' . $apiKey;
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return 'Ошибка при подключении к API: ' . $curlError;
    }

    $responseData = json_decode($response, true);

    if (isset($responseData['error'])) {
        return 'Ошибка API: ' . $responseData['error']['message'];
    }

    if (!empty($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        return trim($responseData['candidates'][0]['content']['parts'][0]['text']);
    } else {
        return 'Ответ пустой. Данные от API: ' . print_r($responseData, true);
    }
}

$telegram_id = isset($_GET['telegram_id']) ? $_GET['telegram_id'] : null;
$is_authorized = false;
$first_name = null;
$userId = null;

if ($telegram_id) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE idtelegram = ?');
    $stmt->execute([$telegram_id]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['task2'] == 1) {
            header('Location: index.php?telegram_id=' . urlencode(htmlspecialchars($telegram_id, ENT_QUOTES, 'UTF-8')) . '&page=tasks');
            exit();
        }

        $is_authorized = true;
        $first_name = $user['first_name'];
        $userId = $user['id'];
    }
}

$statusMessage = "Введите код для проверки.";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $submittedPrompt = isset($_POST['prompt']) ? trim($_POST['prompt']) : '';

    if (empty($submittedPrompt)) {
        $statusMessage = 'Запрос не передан.';
    } else {
        $finalPrompt = "Проверьте следующий код на правильность логики. Задание: Создайте страницу о себе с использованием CSS. Если код правильный, ответьте одним словом *Правильно*, если нет, то *Неправильно*: \n" . $submittedPrompt;

        $apiResponse = generateContentWithGemini($finalPrompt);
        $statusMessage = $apiResponse;

        if (mb_strtolower(trim($apiResponse)) === 'правильно') {
            if ($userId !== null) {
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare('UPDATE users SET coin = coin + 300, task2 = 1 WHERE id = ?');
                    $stmt->execute([$userId]);
                    $pdo->commit();
                    header('Location: index.php?telegram_id=' . urlencode(htmlspecialchars($telegram_id, ENT_QUOTES, 'UTF-8')) . '&page=tasks');
                    exit();
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $statusMessage = 'Ошибка при обновлении данных: ' . $e->getMessage();
                }
            } else {
                $statusMessage = 'Пользователь не найден.';
            }
        } elseif (mb_strtolower(trim($apiResponse)) === 'неправильно') {
            $statusMessage = 'Ваш код неверен. Попробуйте еще раз.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Проверка кода</title>
    <style>
 
 body {
    font-family: Arial, sans-serif;
    background: linear-gradient(to bottom, #0000FF, #000000);
    color: white; /* Изменено на белый цвет */
    margin: 0;
    padding: 0;
}

h1 {
    text-align: center;
    color: white; /* Изменено на белый цвет */
}

.status {
    margin: 10px 0;
    font-size: 14px;
    color: #d9534f; /* Красный для ошибок (можно оставить) */
}
.modal-header {
    font-size: 18px;
    margin-bottom: 10px;
    font-weight: bold;
    text-align: center;
    color: black; /* Изменено на черный цвет */
}

.modal-body {
    max-height: 200px;
    overflow-y: auto;
    margin-bottom: 20px;
    color: black; /* Изменено на черный цвет */
}



        .container {
    width: 90%;
    max-width: 600px;
    margin: 50px auto;
    padding: 20px;
    background: linear-gradient(to bottom, rgba(50, 50, 50, 0.8), rgba(0, 0, 0, 0.8)); /* Серо-черный градиент с прозрачностью */
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}



        .submission-area {
            margin-top: 20px;
        }

        textarea {
            width: 100%;
            height: 150px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            resize: none;
            font-size: 16px;
        }

      
        .submit-btn {
    width: 100%;
    padding: 10px;
    background: linear-gradient(to right, #FF69B4, #8A2BE2); /* Розово-фиолетовый градиент */
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s;
}

.submit-btn:hover {
    background: linear-gradient(to right, #FF1493, #7B68EE); /* Темнее при наведении */
}

.error-btn {
    width: 100%;
    padding: 10px;
    background: linear-gradient(to right, #FF69B4, #8A2BE2); /* Розово-фиолетовый градиент */
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    margin-top: 10px;
    transition: background-color 0.3s;
}

.error-btn:hover {
    background: linear-gradient(to right, #FF1493, #7B68EE); /* Темнее при наведении */
}

.back-btn {
    padding: 10px;
    background: linear-gradient(to right, #FF69B4, #8A2BE2); /* Розово-фиолетовый градиент */
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    margin-top: 10px;
    width: 100%;
    transition: background-color 0.3s;
}

.back-btn:hover {
    background: linear-gradient(to right, #FF1493, #7B68EE); /* Темнее при наведении */
}

        /* Модальное окно */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: none;
            z-index: 1000;
        }

        .modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            width: 80%;
            max-width: 400px;
            z-index: 1001;
        }

       
        

        .close-button {
            padding: 10px;
            background-color: #d9534f; /* Red */
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
        }

        .close-button:hover {
            background-color: #c9302c; /* Darker red */
        }

        @media (max-width: 600px) {
            .container {
                width: 95%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Создайте страницу о себе с использованием CSS.</h1>
        <img src="https://media1.tenor.com/m/MTHZdYrxvN8AAAAC/spongebob-thinking.gif" alt="SpongeBob Thinking" style="display: block; margin: 0 auto; max-width: 30%; height: auto; margin-bottom: 20px;">

        <p>Здравствуйте, <?php echo htmlspecialchars($first_name); ?>!</p>
        <div class="submission-area">
            <form method="post">
                <textarea name="prompt" id="codeInput" placeholder="Введите ваш код здесь..."></textarea>
                <div class="status"><?php echo htmlspecialchars($statusMessage); ?></div>
                <button type="submit" class="submit-btn">Проверить код</button>
            </form>
            <button class="error-btn" onclick="fetchErrors()">Показать слабые стороны кода (нужно ввести код в текстовое поле)</button>
        </div>
        <button class="back-btn" onclick="window.history.back()">Назад</button>

    </div>

    <div class="modal-overlay" id="modalOverlay" style="display:none;"></div>
    <div class="modal" id="errorModal" style="display:none;">
    <div class="modal-header">Слабые стороны кода. Ожидание 1-3минуты</div>
<div class="modal-body" id="errorList"></div>

        <button class="close-button" onclick="closeModal()">Закрыть</button>
    </div>

    <script>
        const apiKey = 'AIzaSyDltcERIhqdcq5aWuU0m1nzQ6VJI6FXIMo'; // Замените на ваш фактический API-ключ

        // Функция для открытия модального окна с ошибками
        function fetchErrors() {
            const codeInput = document.getElementById('codeInput').value; // Получаем код из текстового поля
            if (codeInput.trim() === '') {
                alert('Сначала введите код для анализа.');
                return;
            }
            openModal(); // Открыть модальное окно
            fetch('https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' + apiKey, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    contents: [{ parts: [{ text: "Проанализируй этот код и найди слабые зоны кода, а затем напиши мне их в критике.  Также напиши рекомендации по его исправлению:\n" + codeInput }] }]
                })
            })
            .then(response => response.json())
            .then(data => {
                const errorList = document.getElementById('errorList');
                if (data.candidates && data.candidates.length > 0) {
                    errorList.textContent = data.candidates[0].content.parts[0].text;
                } else {
                    errorList.textContent = 'Не удалось получить слабые зоны.';
                }
            })
            .catch(error => {
                console.error('Ошибка запроса:', error);
                document.getElementById('errorList').textContent = 'Ошибка при получении данных.';
            });
        }

        function openModal() {
            document.getElementById('errorModal').style.display = 'block';
            document.getElementById('modalOverlay').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('errorModal').style.display = 'none';
            document.getElementById('modalOverlay').style.display = 'none';
        }
    </script>
</body>
</html>
