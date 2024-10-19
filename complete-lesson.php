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

// Получаем данные из POST-запроса
$course_id = isset($_POST['course_id']) ? $_POST['course_id'] : null;
$lessonID = isset($_POST['lessonID']) ? $_POST['lessonID'] : null;
$telegram_id = isset($_POST['telegram_id']) ? $_POST['telegram_id'] : null;

// Проверяем, переданы ли необходимые данные
if (!$course_id || !$lessonID || !$telegram_id) {
    echo json_encode(['error' => 'Ошибка: отсутствуют необходимые данные.']);
    exit();
}

// Получаем прогресс курса для пользователя
$stmt = $pdo->prepare("SELECT * FROM course_progress WHERE user_id = :user_id AND course_id = :course_id");
$stmt->execute([':user_id' => $telegram_id, ':course_id' => $course_id]);
$course_progress = $stmt->fetch(PDO::FETCH_ASSOC);

// Если прогресс не найден, вставляем новую запись
if (!$course_progress) {
    // Предположим, что значения $total_modules и $total_lessons известны и определяются заранее.
    $total_modules = 10; // Здесь указываем общее количество модулей
    $total_lessons = 50; // Здесь указываем общее количество уроков

    $stmt = $pdo->prepare("
        INSERT INTO course_progress (user_id, course_id, completed_modules, completed_lessons, total_modules, total_lessons, completion_status, created_at) 
        VALUES (:user_id, :course_id, 0, 0, :total_modules, :total_lessons, 'in_learning', NOW())
    ");
    $stmt->execute([
        ':user_id' => $telegram_id,
        ':course_id' => $course_id,
        ':total_modules' => $total_modules,
        ':total_lessons' => $total_lessons
    ]);

    // После вставки обновляем данные
    $course_progress = $pdo->lastInsertId(); // Можно использовать lastInsertId, если требуется
}

// Обновляем количество завершённых уроков
$update_stmt = $pdo->prepare("UPDATE course_progress SET completed_lessons = :completed_lessons WHERE user_id = :user_id AND course_id = :course_id");
$update_stmt->execute([
    ':completed_lessons' => $lessonID + 1,
    ':user_id' => $telegram_id,
    ':course_id' => $course_id
]);

echo json_encode(['status' => 'success', 'message' => 'Урок пройден']);
