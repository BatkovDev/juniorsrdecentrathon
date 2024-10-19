<?php
session_start();

// Параметры подключения к базе данных
$host = 'localhost';
$db   = 'broldru_deni';
$user = 'broldru_deni';
$pass = 'Sa4_nambe8';
$charset = 'utf8mb4';

// Настройка DSN и опций PDO
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

$telegram_id = isset($_GET['telegram_id']) ? $_GET['telegram_id'] : null;

if ($telegram_id === null) {
    echo 'Ошибка: telegram_id не передан.';
    ?>
    <script>window.location.href = 'https://brold.ru/index.php';</script>
    <?php
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE idtelegram = ?");
$stmt->execute([$telegram_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo 'Ошибка: пользователь не найден.';
    ?>
    <script>window.location.href = 'https://brold.ru/index.php';</script>
    <?php
    exit();
}
$user_id = $telegram_id;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>All tests</title>
    <link rel="stylesheet" href="https://brold.ru/courses-style.css">
    <style>
        /* Добавим стили для кнопок назад */
        .back-arrow {
            position: absolute;
            top: 10px;
            left: 10px;
            font-size: 24px;
            text-decoration: none;
            color: #000;
        }

        .back-button {
            display: block;
            width: calc(100% - 40px);
            margin: 20px auto;
            padding: 10px;
            background-color: #007BFF;
            color: #fff;
            text-align: center;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
            cursor: pointer;
        }

        .back-button:hover {
            background-color: #0056b3;
        }

        body {
            position: relative;
        }
    </style>
</head>
<body style="max-width: 480px;margin: 0 auto;">
    <!-- Стрелка назад в верхнем левом углу -->
    <a href="index.php?telegram_id=<?php echo htmlspecialchars($telegram_id); ?>" class="back-arrow">&#8592;</a>

    <div class="courses">
        <h1>Все курсы</h1>
        <ul class="__list">
        <?php
            $sql = "SELECT id, title, description, created_at FROM courses";

            try {
                $stmt = $pdo->query($sql);
                if ($stmt->rowCount() > 0) {
                    while ($course = $stmt->fetch(PDO::FETCH_ASSOC)) { // Получаем по одной записи за раз
                        $progressStmt = $pdo->prepare("SELECT * FROM course_progress WHERE course_id = :course_id AND user_id = :user_id");
                        $progressStmt->execute(['course_id' => $course['id'], 'user_id' => $user_id]);
                        $course_progress = $progressStmt->fetch(PDO::FETCH_ASSOC);

                        $progress_prc = 0;
                        $button_text = 'Пройти курс';

                        if ($course_progress) {
                            if ($course_progress['total_lessons'] > 0) {
                                $progress_prc = ($course_progress['completed_lessons'] / $course_progress['total_lessons']) * 100;
                            } else {
                                $progress_prc = 0;
                            }
                            if ($course_progress['completion_status'] == 'in_learning') {
                                $button_text = 'Продолжить обучение';
                            } elseif ($course_progress['completion_status'] == 'completed') {
                                $button_text = 'Курс пройден';
                            }
                        }

                        echo '
                            <li class="course-item" data-id="' . htmlspecialchars($course['id']) . '">
                                <h2>' . htmlspecialchars($course['title']) . '</h2>
                                <p class="description">' . htmlspecialchars($course['description']) . '</p>
                                <div class="progress">
                                    <h3>Прогресс</h3>
                                    <div class="progress-bar">
                                        <div class="progress-indicator" style="width: '.$progress_prc.'%;"></div>
                                    </div>
                                </div>
                                ' . ($course['id'] ? '<button class="btn continue_course_btn" onclick="openCourse(' . htmlspecialchars($course['id']) . ')">'.htmlspecialchars($button_text).'</button>' : '') . '
                            </li>';
                    }
                } else {
                    echo '<p>Курсы в данный момент отсутствуют.</p>';
                }
            } catch (PDOException $e) {
                die("Ошибка выполнения запроса: ".$e->getMessage());
            }
            ?>
        </ul>
        <!-- Кнопка назад в нижней части страницы -->
        <a href="index.php?telegram_id=<?php echo htmlspecialchars($telegram_id); ?>" class="back-button">Назад</a>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://brold.ru/courses-script.js"></script>
</body>
</html>
