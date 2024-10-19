<?php
$servername = "localhost";
$username = "broldru_deni"; // Укажите имя пользователя
$password = "Sa4_nambe8"; // Укажите пароль
$dbname = "broldru_deni"; // Укажите имя вашей базы данных

// Создаем подключение
$conn = new mysqli($servername, $username, $password, $dbname);

// Проверяем подключение
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
?>
