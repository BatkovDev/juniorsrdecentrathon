<?php
// Подключение к базе данных через PDO
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
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Проверка наличия Telegram ID
$telegram_id = isset($_GET['telegram_id']) ? $_GET['telegram_id'] : null;
$is_authorized = false;
$first_name = null;
$userId = null;
$statusMessage = ''; // Переменная для сообщения об ошибке

if ($telegram_id) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE idtelegram = ?');
    $stmt->execute([$telegram_id]);
    $user = $stmt->fetch();

    if ($user) {
        $is_authorized = true;
        $first_name = $user['first_name'];
        $userId = $user['id']; // Сохраняем ID пользователя
    } else {
        $statusMessage = 'Пользователь не найден. Пожалуйста, проверьте свой Telegram ID.'; // Сообщение об ошибке
    }
}

session_start(); // Начало сессии

// Проверяем, был ли установлен статус в сессии
$status = isset($_SESSION['status']) ? $_SESSION['status'] : null;

// Проверяем, был ли статус 'Правильно' и обновляем значение task1 в базе данных
if ($status === 'Правильно' && $userId !== null) {
    // Начинаем транзакцию для обеспечения целостности данных
    $pdo->beginTransaction();
    try {
        // Обновляем информацию пользователя: добавляем коины и устанавливаем task1 = 1
        $stmt = $pdo->prepare('UPDATE users SET coin = coin + 300, task1 = 1 WHERE id = ?');
        $stmt->execute([$userId]);

        // Фиксируем транзакцию
        $pdo->commit();

        // Удаляем статус из сессии, чтобы избежать повторного выполнения
        unset($_SESSION['status']);

        // Перенаправление на homework.php
        header('Location: homework.php');
        exit();
    } catch (Exception $e) {
        // Откатываем транзакцию в случае ошибки
        $pdo->rollBack();
        echo 'Ошибка при обновлении данных: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <title>Домашние задания</title>
    <style>
         body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #0a0a0a, #1e1e1e);
            color: #ffffff;
            margin: 0;
            padding: 20px;
            position: relative; /* Добавьте это для абсолютного позиционирования звезд */
        }

        .star {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.8);
            opacity: 0.9;
            pointer-events: none;
            z-index: -1; 
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.5); 
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }
       

        .oval-container3 {
    position: absolute; /* Абсолютное позиционирование для контейнера */
    top: 10px; /* Отступ сверху */
    right: 10px; /* Отступ справа */
    background: rgba(255, 255, 255, 0.2); /* Белый цвет с прозрачностью */
    border-radius: 50%; /* Овальная форма */
    padding: 10px; /* Отступ внутри контейнера */
    display: flex; /* Центрируем содержимое */
    justify-content: center; /* Центрируем содержимое по горизонтали */
    align-items: center; /* Центрируем содержимое по вертикали */
}
    

.oval-container {
    position: absolute; /* Абсолютное позиционирование для контейнера */
    top: 10px; /* Отступ сверху */
    right: 10px; /* Отступ справа */
    background: rgba(255, 255, 255, 0.2); /* Белый цвет с прозрачностью */
    border-radius: 50%; /* Овальная форма */
    padding: 10px; /* Отступ внутри контейнера */
    display: flex; /* Центрируем содержимое */
    justify-content: center; /* Центрируем содержимое по горизонтали */
    align-items: center; /* Центрируем содержимое по вертикали */
}

.module-icon {
    width: 50px; /* Ширина изображения (можно изменить) */
    height: auto; /* Автоматическая высота, чтобы сохранить пропорции */
}



        .module-container {
            background: rgba(46, 46, 46, 0.8);
            border-radius: 8px;
            padding: 15px; /* Отступы для контейнера модуля */
            margin-bottom: 20px;
            position: relative; /* Устанавливаем относительное позиционирование для контейнера */
        }

        .module-title {
    font-size: 36px; /* Размер шрифта */
    font-weight: bold; /* Жирный шрифт */
    color: #ffffff; /* Цвет текста */
    margin: 0; /* Убираем отступы */
    padding: 10px 0; /* Отступы сверху и снизу для вертикального центрирования */
    text-align: left; /* Выравнивание текста по левому краю */
}
.button-container {
    background: rgba(255, 255, 255, 0.2); /* Белый цвет с прозрачностью */
    border-radius: 5px; /* Скругление углов */
    padding: 7px; /* Отступы внутри контейнера */
    display: flex; /* Центрируем содержимое */
    align-items: center; /* Центрируем содержимое по вертикали */
    justify-content: center; /* Центрируем содержимое по горизонтали */
    width: 97%; /* Устанавливаем ширину на 100% */
    height: 50px; /* Установите нужную высоту для контейнера */
    margin-top: 20px; /* Отступ сверху */
    position: relative; /* Для абсолютного позиционирования внутри */
}

.coin-text {
    font-size: 20px; /* Размер текста */
    color: #ffffff; /* Цвет текста */
    margin-left: 7px; /* Отступ слева для текста */
}

.button-image {
    width: 30px; /* Ширина изображения (можно изменить) */
    height: auto; /* Автоматическая высота, чтобы сохранить пропорции */
    margin-right: 10px; /* Отступ справа для текста */
}



        .assignments-container {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            justify-content: flex-start;
            width: 100%;
            margin-left: 20px;
        }

        .assignments {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
            width: 100%; /* Занимаем полную ширину */
        }

        .assignment-card {
            background: rgba(46, 46, 46, 0.8);
            border-radius: 8px;
            padding: 10px;
            margin: 5px;
            width: calc(30% - 10px); /* Ширина карточек */
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            transition: box-shadow 0.3s, transform 0.3s;
        }

        .assignment-card:hover {
            box-shadow: 0 8px 20px rgba(255, 255, 255, 0.5);
            transform: scale(1.05);
        }

        .details-btn {
            background: linear-gradient(90deg, #ff0080, #8000ff);
            color: #ffffff;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            display: inline-block;
            transition: background 0.3s;
        }

        .details-btn:hover {
            background: linear-gradient(90deg, #8000ff, #ff0080);
        }

        .disabled-btn {
            background: linear-gradient(90deg, #00ff00, #008000);
            color: #ffffff;
            cursor: not-allowed;
            pointer-events: none;
        }

        .status-message {
            margin: 20px;
            color: #4CAF50;
            font-weight: bold;
        }

        .error-message {
            margin: 20px;
            color: #FF0000;
            font-weight: bold;
        }

        /* Мобильная адаптивность */
        @media (max-width: 768px) {
            .assignments-container {
                flex-direction: column; /* Вертикальное размещение контейнеров заданий */
                align-items: center; /* Центрируем контейнеры */
                margin-left: 0; /* Убираем отступы слева */
            }

            .assignment-card {
                width: calc(90% - 10px); /* Уменьшаем ширину карточек для мобильных */
                margin: 5px 0; /* Убираем боковые отступы */
            }
        }
       
        .bottom-center {
    display: flex;
    justify-content: center; /* Центрируем по горизонтали */
    align-items: center; /* Центрируем по вертикали */
    margin-top: 20px; /* Отступ сверху */
}

.bottom-image {
    width: 50px; /* Измените ширину по необходимости */
    height: auto; /* Высота будет автоматически изменяться в зависимости от ширины */
    margin-right: 10px; /* Отступ справа для текста */
}

.coin-text {
    font-size: 24px; /* Размер текста */
    color: #ffffff; /* Цвет текста */
}
.oval-container2 {
    background: rgba(255, 255, 255, 0.2); /* Белый цвет с прозрачностью */
    border-radius: 100%; /* Овальная форма */
    padding: 5px; /* Отступы внутри контейнера */
    display: flex; /* Центрируем содержимое */
    flex-direction: column; /* Вертикальное размещение содержимого */
    align-items: center; /* Центрируем содержимое по горизонтали */
    justify-content: center; /* Центрируем содержимое по вертикали */
    margin-top: 20px; /* Отступ сверху */
}
.button-image2 {
    width: 40px; /* Ширина изображения (можно изменить) */
    height: auto; /* Автоматическая высота для сохранения пропорций */
    margin-left: 10px; /* Отступ слева для изображения */
}
.oval-container-wrapper {
    display: flex; /* Используем flexbox для расположения контейнеров */
    justify-content: flex-start; /* Выравниваем по левому краю */
    align-items: center; /* Центрируем по вертикали */
    margin-bottom: 20px; /* Отступ снизу для контейнера */
}
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Домашние задания</h1>
        </header>
        
        <div class="module-container">
    <div class="module-title">WEB</div> <!-- Название модуля -->
    <div class="oval-container-wrapper">
        <div class="oval-container3">
            <img src="https://www.clipartmax.com/png/middle/99-999829_css34-css-3-logo-transperant.png" alt="HTML Icon" class="module-icon">
        </div>
        <div class="oval-container">
            <img src="https://w7.pngwing.com/pngs/147/317/png-transparent-html-computer-icons-web-development-bootstrap-world-wide-web-text-trademark-orange.png" alt="HTML Icon" class="module-icon">
        </div>
    </div>

    <div class="assignments-container">
        <main class="assignments">
            <?php if ($status === 'Правильно'): ?>
                <div class="status-message">Задание выполнено!</div>
            <?php elseif ($statusMessage): ?>
                <div class="error-message"><?= htmlspecialchars($statusMessage, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($is_authorized): ?>
                <div class="assignment-card">
                    <img src="https://media.baamboozle.com/uploads/images/21444/1656760558_325129_gif-url.gif" alt="Задание 1" style="width: 45%;">
                    <p>Создайте базовую структуру веб-страницы с использованием HTML. №1</p>
                    <a href="task.php?task=1" class="details-btn <?php echo ($user && $user['task1'] == 1) ? 'disabled-btn' : ''; ?>" 
                    <?php echo ($user && $user['task1'] == 1) ? 'onclick="return false;"' : ''; ?>>
                    <?php echo ($user && $user['task1'] == 1) ? 'Задание завершено' : 'Открыть задание'; ?>
                    </a>
                </div>

                <div class="assignment-card">
                    <img src="https://media.baamboozle.com/uploads/images/21444/1656760558_325129_gif-url.gif" alt="Задание 2" style="width: 45%;">
                    <p>Создайте страницу о себе с использованием CSS. №2</p>
                    <a href="task2.php" class="details-btn <?php echo ($user && $user['task2'] == 1) ? 'disabled-btn' : ''; ?>">
                    <?php echo ($user && $user['task2'] == 1) ? 'Задание завершено' : 'Открыть задание'; ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="error-message">Вы не авторизованы для просмотра заданий.</div>
            <?php endif; ?>
        </main>
    </div>
