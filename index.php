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
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

if (isset($_GET['action']) && $_GET['action'] === 'fetch_rating') {
    if (isset($_GET['telegram_id'])) {
        $telegram_id = $_GET['telegram_id'];

        $stmt = $pdo->prepare('SELECT * FROM users WHERE idtelegram = ?');
        $stmt->execute([$telegram_id]);
        $user = $stmt->fetch();

        if ($user) {
            $stmt = $pdo->prepare('SELECT first_name, coin FROM users ORDER BY coin DESC LIMIT 3');
            $stmt->execute();
            $top_users = $stmt->fetchAll();

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'top_users' => $top_users]);
            exit;
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$telegram_id = isset($_GET['telegram_id']) ? $_GET['telegram_id'] : null;
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$is_new_user = false;
$first_name = null;
$junicoins = null;
$is_authorized = false;
$top_users = [];
$statusMessage = '';
$progress = 0;

if ($telegram_id) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE idtelegram = ?');
    $stmt->execute([$telegram_id]);
    $user = $stmt->fetch();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nickname'])) {
        $nickname = trim($_POST['nickname']);

        if ($nickname === '') {
            $error_message_modal = "Никнейм не может быть пустым.";
        } else {
            if ($user) {
                $stmt = $pdo->prepare('UPDATE users SET first_name = ? WHERE idtelegram = ?');
                $stmt->execute([$nickname, $telegram_id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO users (idtelegram, first_name) VALUES (?, ?)');
                $stmt->execute([$telegram_id, $nickname]);
            }

            $stmt = $pdo->prepare('SELECT * FROM users WHERE idtelegram = ?');
            $stmt->execute([$telegram_id]);
            $user = $stmt->fetch();

            $first_name = $user['first_name'];
            $junicoins = $user['coin'];
            $is_new_user = false;

            header("Location: ?telegram_id=" . urlencode($telegram_id) . "&page=" . urlencode($page));
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase_ai'])) {
        if ($user && $user['AI'] !== 'yes') {
            if ($user['coin'] >= 500) {
                try {
                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare('UPDATE users SET coin = coin - 500 WHERE idtelegram = ?');
                    $stmt->execute([$telegram_id]);

                    $stmt = $pdo->prepare('UPDATE users SET AI = "yes" WHERE idtelegram = ?');
                    $stmt->execute([$telegram_id]);

                    $pdo->commit();

                    $stmt = $pdo->prepare('SELECT * FROM users WHERE idtelegram = ?');
                    $stmt->execute([$telegram_id]);
                    $user = $stmt->fetch();

                    $first_name = $user['first_name'];
                    $junicoins = $user['coin'];
                    $is_new_user = false;

                    $statusMessage = 'Подписка AI Juniors.kz успешно приобретена!';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $statusMessage = 'Ошибка при покупке подписки: ' . $e->getMessage();
                }
            } else {
                $statusMessage = 'Недостаточно монет для покупки подписки.';
            }
        } else {
            $statusMessage = 'Подписка уже приобретена.';
        }
    }

    if ($user) {
        if ($user['first_name'] === '0') {
            $is_new_user = true;
        } else {
            $first_name = $user['first_name'];
            $junicoins = $user['coin'];
        }

        if ($user['task1'] == 1) {
            $progress += 50;
        }
        if ($user['task2'] == 1) {
            $progress += 50;
        }
    } else {
        $is_new_user = true;
    }

    $is_authorized = true;
} else {
    $is_authorized = false;
}

if ($is_authorized && $page === 'tasks') {
    if ($user) {
        $userId = $user['id'];
        $first_name = $user['first_name'];
        $statusMessage = '';

        if (isset($_SESSION['status']) && $_SESSION['status'] === 'Правильно') {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('UPDATE users SET coin = coin + 300, task1 = 1 WHERE id = ?');
                $stmt->execute([$userId]);

                $pdo->commit();

                unset($_SESSION['status']);

                header('Location: ?telegram_id=' . urlencode($telegram_id) . '&page=tasks');
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $statusMessage = 'Ошибка при обновлении данных: ' . $e->getMessage();
            }
        }

        if (isset($_SESSION['status_task2']) && $_SESSION['status_task2'] === 'Правильно') {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('UPDATE users SET coin = coin + 300, task2 = 1 WHERE id = ?');
                $stmt->execute([$userId]);

                $pdo->commit();

                unset($_SESSION['status_task2']);

                header('Location: ?telegram_id=' . urlencode($telegram_id) . '&page=tasks');
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $statusMessage = 'Ошибка при обновлении данных: ' . $e->getMessage();
            }
        }
    } else {
        $statusMessage = 'Пользователь не найден. Пожалуйста, проверьте свой Telegram ID.';
    }
}

if ($is_authorized && !$is_new_user && $page == 'rating') {
    $stmt = $pdo->prepare('SELECT first_name, coin FROM users ORDER BY coin DESC LIMIT 3');
    $stmt->execute();
    $top_users = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
    <title>Главная страница</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	
    <style>
        body {
            touch-action: manipulation;
            -ms-touch-action: manipulation;
            height: 100vh;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-color-light);
            color: var(--text-color-light);
            transition: background-color 0.3s ease, color 0.3s ease;
            font-size: 18px;
        }

        :root {
            --bg-color-light: #f0f2f5;
            --bg-color-dark: #18191a;
            --text-color-light: #1c1e21;
            --text-color-dark: #e4e6eb;
            --primary-color: #1877f2;
            --nav-bg-light: rgba(255, 255, 255, 0.95);
            --nav-bg-dark: rgba(24, 25, 26, 0.95);
            --nav-link-hover: #166fe5;
            --toggle-bg: #fff;
            --toggle-bg-dark: #242526;
            --card-bg-light: #ffffff;
            --card-bg-dark: #242526;
            --button-hover: #1558b0;
            --button-transfer: #28a745;
            --button-history: #17a2b8;
            --button-transfer-hover: #218838;
            --button-history-hover: #138496;
            --modal-bg: rgba(0, 0, 0, 0.5);
            --modal-content-bg-light: #ffffff;
            --modal-content-bg-dark: #242526;
            --progress-bg: #e0e0e0;
            --progress-bar: #76c7c0;
        }

        body.dark {
            background-color: var(--bg-color-dark);
            color: var(--text-color-dark);
        }

        nav {
            background: var(--nav-bg-light);
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-around;
            position: fixed;
            width: 100%;
            bottom: 0;
            z-index: 100;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            transition: background 0.3s ease;
        }

        body.dark nav {
            background: var(--nav-bg-dark);
            box-shadow: 0 -2px 10px rgba(0,0,0,0.5);
        }

        nav .nav-links {
            display: flex;
            align-items: center;
            justify-content: space-around;
            width: 80%;
        }

        nav .nav-links a {
            margin: 0 5px;
            text-decoration: none;
            color: inherit;
            font-weight: 600;
            font-size: 16px;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: color 0.3s ease;
        }

        nav .nav-links a i {
            font-size: 20px;
            margin-bottom: 3px;
        }

        nav .nav-links a::after {
            content: '';
            display: block;
            width: 0%;
            height: 2px;
            background-color: var(--primary-color);
            transition: width 0.3s;
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
        }

        nav .nav-links a:hover {
            color: var(--primary-color);
        }

        nav .nav-links a:hover::after {
            width: 60%;
        }

        .theme-toggle {
            background: var(--toggle-bg);
            border: none;
            cursor: pointer;
            font-size: 20px;
            outline: none;
            color: var(--primary-color);
            transition: background 0.3s ease, color 0.3s ease;
            border-radius: 50%;
            padding: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
            flex-shrink: 0;
        }

        body.dark .theme-toggle {
            background: var(--toggle-bg-dark);
            color: #f0f2f5;
        }

        .container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
            padding: 20px;
            padding-bottom: 80px;
            background-size: cover;
            background-position: center;
            width: 100%;
            box-sizing: border-box;
        }

        .container h1 {
            font-size: 36px;
            margin-bottom: 20px;
            color: inherit;
        }

        .container p {
            font-size: 20px;
            max-width: 800px;
            color: inherit;
        }

        .nickname-form {
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }

        body.dark .nickname-form {
            background: rgba(24, 25, 26, 0.9);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.7);
        }

        .nickname-form input[type="text"] {
            padding: 10px;
            font-size: 16px;
            border-radius: 6px;
            border: 2px solid var(--primary-color);
            margin-bottom: 15px;
            outline: none;
            width: 100%;
            box-sizing: border-box;
            transition: border 0.3s ease;
        }

        .nickname-form input[type="text"]:focus {
            border-color: var(--nav-link-hover);
        }

        .nickname-form button {
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 6px;
            border: none;
            background: var(--primary-color);
            color: #fff;
            cursor: pointer;
            transition: background 0.3s ease;
            width: 100%;
        }

        .nickname-form button:hover {
            background: var(--button-hover);
        }

        .profile-card {
            background: var(--card-bg-light);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            text-align: left;
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }

        body.dark .profile-card {
            background: var(--card-bg-dark);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
        }

        .profile-card h2 {
            margin-top: 0;
            font-size: 28px;
            color: inherit;
        }

        .profile-details {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 15px;
        }

        .profile-details div {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #ccc;
            transition: border-color 0.3s ease;
        }

        body.dark .profile-details div {
            border-color: #444;
        }

        .profile-details div:last-child {
            border-bottom: none;
        }

        .profile-details .label {
            font-weight: 600;
            font-size: 16px;
        }

        .profile-details .value {
            font-size: 16px;
            color: var(--primary-color);
        }

        .additional-actions {
            margin-top: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .additional-actions button {
            padding: 8px 16px;
            font-size: 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.3s ease;
            color: #fff;
        }

        .btn-transfer {
            background: var(--button-transfer);
        }

        .btn-transfer:hover {
            background: var(--button-transfer-hover);
            transform: translateY(-2px);
        }

        .btn-history {
            background: var(--button-history);
        }

        .btn-history:hover {
            background: var(--button-history-hover);
            transform: translateY(-2px);
        }

        .theme-switcher {
            margin-top: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .theme-switcher button {
            padding: 8px 16px;
            font-size: 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            background: var(--primary-color);
            color: #fff;
            transition: background 0.3s ease;
        }

        .theme-switcher button:hover {
            background: var(--nav-link-hover);
        }

        .rating-table {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            margin-top: 20px;
        }

        .rating-entry {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--card-bg-light);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }

        body.dark .rating-entry {
            background: var(--card-bg-dark);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.5);
        }

        .rating-entry .rank {
            font-size: 20px;
            font-weight: bold;
            width: 40px;
            text-align: center;
        }

        .rating-entry .nickname {
            font-size: 16px;
            flex: 1;
            text-align: left;
            margin-left: 15px;
        }

        .rating-entry .coins {
            font-size: 16px;
            color: var(--primary-color);
            width: 120px;
            text-align: right;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 200;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background: var(--modal-bg);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: var(--modal-content-bg-light);
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 350px;
            position: relative;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }

        body.dark .modal-content {
            background: var(--modal-content-bg-dark);
            box-shadow: 0 5px 15px rgba(0,0,0,0.7);
        }

        .close-modal {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close-modal:hover {
            color: #000;
        }

        .modal-content h3 {
            margin-top: 0;
            color: inherit;
        }

        .modal-content input[type="text"] {
            padding: 8px;
            font-size: 16px;
            border-radius: 6px;
            border: 2px solid var(--primary-color);
            margin-bottom: 15px;
            outline: none;
            width: 100%;
            box-sizing: border-box;
            transition: border 0.3s ease;
        }

        .modal-content input[type="text"]:focus {
            border-color: var(--nav-link-hover);
        }

        .modal-content button {
            padding: 8px 16px;
            font-size: 16px;
            border: none;
            border-radius: 6px;
            background: var(--primary-color);
            color: #fff;
            cursor: pointer;
            transition: background 0.3s ease;
            width: 100%;
        }

        .modal-content button:hover {
            background: var(--button-hover);
        }

        .shop-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            text-align: left;
        }

        .shop-item {
            background: var(--card-bg-light);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }

        body.dark .shop-item {
            background: var(--card-bg-dark);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.5);
        }

        .shop-item h3 {
            margin: 0 0 10px 0;
            font-size: 20px;
        }

        .shop-item p {
            margin: 0 0 10px 0;
            font-size: 16px;
        }

        .shop-item .price {
            font-size: 18px;
            color: var(--primary-color);
            font-weight: bold;
        }

        .tasks-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            text-align: left;
        }

        .tasks-container .module-container {
            background: rgba(46, 46, 46, 0.8);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            position: relative;
        }

        .tasks-container .module-title {
            font-size: 36px;
            font-weight: bold;
            color: #ffffff;
            margin: 0;
            padding: 10px 0;
            text-align: left;
        }

        .tasks-container .oval-container-wrapper {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin-bottom: 20px;
        }

        .tasks-container .oval-container3,
        .tasks-container .oval-container {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            padding: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 10px;
        }

        .tasks-container .module-icon {
            width: 50px;
            height: auto;
        }

        .tasks-container .button-container {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            padding: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 97%;
            height: 50px;
            margin-top: 20px;
            position: relative;
        }

        .tasks-container .coin-text {
            font-size: 20px;
            color: #ffffff;
            margin-left: 7px;
        }

        .tasks-container .button-image {
            width: 30px;
            height: auto;
            margin-right: 10px;
        }

        .tasks-container .button-image2 {
            width: 40px;
            height: auto;
            margin-left: 10px;
        }

        .tasks-container .assignments-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
            width: 100%;
            margin-left: 20px;
        }

        .tasks-container .assignments {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
            width: 100%;
        }

        .tasks-container .assignment-card {
            background: rgba(46, 46, 46, 0.8);
            border-radius: 8px;
            padding: 10px;
            margin: 5px;
            width: calc(30% - 10px);
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            transition: box-shadow 0.3s, transform 0.3s;
        }

        .tasks-container .assignment-card:hover {
            box-shadow: 0 8px 20px rgba(255, 255, 255, 0.5);
            transform: scale(1.05);
        }

        .tasks-container .details-btn {
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
        .tasks-container .details-btn:hover {
            background: linear-gradient(90deg, #8000ff, #ff0080);
        }

        .tasks-container .disabled-btn {
            background: linear-gradient(90deg, #00ff00, #008000);
            color: #ffffff;
            cursor: not-allowed;
            pointer-events: none;
        }

        .tasks-container .status-message {
            margin: 20px;
            color: #4CAF50;
            font-weight: bold;
        }

        .tasks-container .error-message {
            margin: 20px;
            color: #FF0000;
            font-weight: bold;
        }

        .progress-container {
            width: 100%;
            max-width: 500px;
            margin: 20px auto;
            text-align: left;
            position: relative;
        }

        .progress-container h3 {
            margin-bottom: 10px;
            font-size: 20px;
            color: inherit;
        }

        .progress-percentage {
            text-align: right;
            margin-bottom: 5px;
        }

        .progress-percentage p {
            margin: 0;
            font-size: 16px;
            color: inherit;
        }

        .progress-bar-background {
            width: 100%;
            background-color: var(--progress-bg);
            border-radius: 10px;
            overflow: hidden;
            height: 25px;
        }

        .progress-bar-fill {
            height: 100%;
            width: 0%;
            background-color: var(--progress-bar);
            border-radius: 10px 0 0 10px;
            transition: width 0.5s ease-in-out;
        }

        @media (max-width: 768px) {
            nav .nav-links a {
                font-size: 14px;
            }

            nav .nav-links a i {
                font-size: 18px;
            }

            .container h1 {
                font-size: 28px;
            }

            .container p {
                font-size: 18px;
            }

            .nickname-form {
                padding: 15px;
            }

            .nickname-form input[type="text"], .nickname-form button {
                font-size: 14px;
                padding: 8px;
            }

            .profile-card {
                padding: 15px;
            }

            .profile-card h2 {
                font-size: 24px;
            }

            .profile-details .label, .profile-details .value {
                font-size: 14px;
            }

            .additional-actions button {
                font-size: 14px;
                padding: 6px 12px;
            }

            .theme-switcher button {
                font-size: 12px;
                padding: 6px 12px;
            }

            .rating-entry .rank {
                font-size: 18px;
                width: 35px;
            }

            .rating-entry .nickname {
                font-size: 14px;
                margin-left: 10px;
            }

            .rating-entry .coins {
                font-size: 14px;
                width: 100px;
            }

            .shop-item h3 {
                font-size: 18px;
            }

            .shop-item p, .shop-item .price {
                font-size: 14px;
            }

            .tasks-container .assignment-card {
                width: calc(90% - 10px);
                margin: 5px 0;
            }

            .tasks-container .coin-text {
                font-size: 24px;
            }

            .progress-container h3 {
                font-size: 18px;
            }

            .progress-bar-background {
                height: 20px;
            }

            .progress-percentage p {
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .container h1 {
                font-size: 24px;
            }

            .container p {
                font-size: 16px;
            }

            .nickname-form {
                padding: 10px;
            }

            .nickname-form input[type="text"], .nickname-form button {
                font-size: 14px;
                padding: 6px;
            }

            .profile-card h2 {
                font-size: 20px;
            }

            .profile-details .label, .profile-details .value {
                font-size: 12px;
            }

            .additional-actions button {
                font-size: 12px;
                padding: 5px 10px;
            }

            .theme-switcher button {
                font-size: 10px;
                padding: 5px 10px;
            }

            .rating-entry .rank {
                font-size: 16px;
                width: 30px;
            }

            .rating-entry .nickname {
                font-size: 12px;
                margin-left: 8px;
            }

            .rating-entry .coins {
                font-size: 12px;
                width: 80px;
            }

            .shop-item h3 {
                font-size: 16px;
            }

            .shop-item p, .shop-item .price {
                font-size: 12px;
            }

            .tasks-container .assignment-card {
                width: calc(90% - 10px);
                margin: 5px 0;
            }

            .tasks-container .coin-text {
                font-size: 24px;
            }

            .progress-container h3 {
                font-size: 16px;
            }

            .progress-percentage p {
                font-size: 12px;
            }

            .progress-bar-background {
                height: 20px;
            }
        }
		:root {
    --bg-color-light: #f0f2f5;
    --bg-color-dark: #18191a;
    --text-color-light: #1c1e21;
    --text-color-dark: #e4e6eb;
    --primary-color: #1877f2;
    --outline-color-light: #fff; /* Обводка для светлой темы - белая */
    --outline-color-dark: #000; /* Обводка для тёмной темы - чёрная */
}

body {
    background-color: var(--bg-color-light);
    color: var(--text-color-light);
    transition: background-color 0.3s ease, color 0.3s ease;
}

body.dark {
    background-color: var(--bg-color-dark);
    color: var(--text-color-dark);
}

.header-container {
    display: flex;
    justify-content: flex-start;
    align-items: center;
    padding: 10px;
    position: absolute;
    top: 0;
    left: 0;
    z-index: 1000;
    background-color: transparent;
}

.user-info {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: inherit;
    cursor: pointer;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #b0b0b0;
    margin-right: 10px;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
}

.user-avatar::before {
    content: "\f007"; /* Код иконки пользователя Font Awesome */
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    color: white; /* Иконка белая, чтобы быть контрастной на синем фоне */
    font-size: 20px;
}

.user-name {
    font-size: 18px;
    font-weight: bold;
    color: var(--text-color-light); /* Текст по умолчанию для светлой темы */
    
    transition: color 0.3s ease, text-shadow 0.3s ease;
}

body.dark .user-name {
    color: var(--text-color-dark); /* Тёмный текст для тёмной темы */
}

.user-info:hover .user-name {
    color: var(--primary-color); /* При наведении текст становится синим */
}

body.dark .user-avatar::before {
    color: var(--text-color-dark); /* Иконка пользователя становится темной на синем фоне в тёмной теме */
}



    </style>
</head>
<body>

<?php if ($is_authorized): ?>
    <?php if ($is_new_user): ?>
        <div class="container">
            <div class="nickname-form">
                <h1>Придумайте или измените никнейм</h1>
                <?php if (isset($error_message_modal)): ?>
                    <p style="color: red;"><?= htmlspecialchars($error_message_modal) ?></p>
                <?php endif; ?>
                <form method="POST">
                    <input type="text" name="nickname" placeholder="Введите ваш никнейм" required>
                    <button type="submit">Сохранить</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="container">
            <?php if ($page == 'profile'): ?>
                <div class="profile-card">
                    <h2>Личный кабинет</h2>
                    <div class="profile-details">
                        <div>
                            <span class="label">Никнейм:</span>
                            <span class="value"><?= htmlspecialchars($first_name) ?></span>
                        </div>
                        <div>
                            <span class="label">JuniCoins:</span>
                            <span class="value"><?= htmlspecialchars($junicoins) ?></span>
                        </div>
                        <?php if ($user['AI'] === 'yes'): ?>
                            <div>
                                <span class="label">AI Juniors.kz:</span>
                                <span class="value">Активно</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="additional-actions">
                    </div>
                    <div class="theme-switcher">
                        <button id="profileThemeToggle">Сменить тему</button>
                    </div>
                    <button id="openModalBtn" style="margin-top: 15px; padding: 8px 16px; font-size: 16px; border: none; border-radius: 6px; background: var(--primary-color); color: #fff; cursor: pointer; transition: background 0.3s ease;">Сменить никнейм</button>

                    <div id="nicknameModal" class="modal">
                        <div class="modal-content">
                            <span class="close-modal">&times;</span>
                            <h3>Изменить никнейм</h3>
                            <?php if (isset($error_message_modal)): ?>
                                <p style="color: red;"><?= htmlspecialchars($error_message_modal) ?></p>
                            <?php endif; ?>
                            <form method="POST">
                                <input type="text" name="nickname" placeholder="Новый никнейм" required>
                                <button type="submit">Обновить</button>
                            </form>
                        </div>
                    </div>

                    <?php if ($user['AI'] === 'yes'): ?>
                         <button onclick="location.href='ai.php?telegram_id=<?= urlencode($telegram_id) ?>'" style="margin-top: 20px; padding: 10px 20px; font-size: 16px; border: none; border-radius: 6px; background: #1877f2; color: #fff; cursor: pointer; transition: background 0.3s ease;">AI Juniors.kz</button>
                    <?php endif; ?>
                </div>
            <?php elseif ($page == 'rating'): ?>
                <h1>Рейтинг студентов</h1>
                <?php if ($statusMessage): ?>
                    <div class="status-message"><?= htmlspecialchars($statusMessage) ?></div>
                <?php endif; ?>
                <div class="rating-table" id="ratingTable">
                    <?php if (!empty($top_users)): ?>
                        <?php foreach ($top_users as $index => $top_user): ?>
                            <div class="rating-entry">
                                <div class="rank"><?= $index + 1 ?></div>
                                <div class="nickname"><?= htmlspecialchars($top_user['first_name']) ?></div>
                                <div class="coins"><?= htmlspecialchars($top_user['coin']) ?> JuniCoins</div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Рейтинг пока пуст.</p>
                    <?php endif; ?>
                </div>
            <?php elseif ($page == 'shop'): ?>
                <h1>Магазин</h1>
                <div class="shop-container">
                    <div class="shop-item">
                        <h3>AI Juniors.kz Подписка</h3>
                        <p>Доступ к эксклюзивным материалам и AI-инструментам.</p>
                        <div class="price">500 JuniCoins</div>
                        <?php if ($user['AI'] === 'yes'): ?>
                            <button class="disabled-btn" disabled>Приобретено</button>
                        <?php elseif ($user['coin'] >= 500): ?>
                            <form method="POST">
                                <button type="submit" name="purchase_ai">Купить</button>
                            </form>
                        <?php else: ?>
                            <button class="disabled-btn" disabled>Недостаточно монет</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($page == 'tasks'): ?>
                <h1>Домашние задания</h1>
                <div class="tasks-container">
                    <div class="module-container">
                        <div class="module-title">WEB</div>
                        
                        <div class="assignments-container">
                            <main class="assignments">
                                <?php if ($statusMessage): ?>
                                    <div class="error-message"><?= htmlspecialchars($statusMessage, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                                <?php if ($is_authorized): ?>
                                    <div class="assignment-card">
                                        <img src="https://media.baamboozle.com/uploads/images/21444/1656760558_325129_gif-url.gif" alt="Задание 1" style="width: 45%;">
                                        <p>Создайте базовую структуру веб-страницы с использованием HTML. №1</p>
                                        <a href="task.php?task=1&telegram_id=<?= urlencode($telegram_id) ?>" class="details-btn <?php echo ($user && $user['task1'] == 1) ? 'disabled-btn' : ''; ?>" 
                                        <?php echo ($user && $user['task1'] == 1) ? 'onclick="return false;"' : ''; ?>>
                                        <?php echo ($user && $user['task1'] == 1) ? 'Задание завершено' : 'Решить задание          (+300 coins)'; ?>
                                        </a>
                                    </div>

                                    <div class="assignment-card">
                                        <img src="https://media.baamboozle.com/uploads/images/21444/1656760558_325129_gif-url.gif" alt="Задание 2" style="width: 45%;">
                                        <p>Создайте страницу о себе с использованием CSS. №2</p>
                                        <a href="task2.php?telegram_id=<?= urlencode($telegram_id) ?>" class="details-btn <?php echo ($user && $user['task2'] == 1) ? 'disabled-btn' : ''; ?>" 
                                        <?php echo ($user && $user['task2'] == 1) ? 'onclick="return false;"' : ''; ?>>
                                        <?php echo ($user && $user['task2'] == 1) ? 'Задание завершено' : 'Решить задание            (+300 coins)'; ?>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="error-message">Вы не авторизованы для просмотра заданий.</div>
                                <?php endif; ?>
                            </main>
                        </div>
                        
                    </div>
                </div>
            <?php else: ?>
			
                <h1>Добро пожаловать, <?= htmlspecialchars($first_name) ?>!</h1>
                <p>Вы успешно авторизовались через Telegram.</p>
                <div class="progress-container">
                    <h3>Прогресс заданий</h3>
                    <div class="progress-percentage">
                        <p><?= $progress ?>% / 100%</p>
                    </div>
                    <div class="progress-bar-background">
                        <div class="progress-bar-fill" style="width: <?= $progress ?>%;"></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <div class="header-container">
        <a href="?telegram_id=<?= urlencode($telegram_id) ?>&page=profile" class="user-info">
            <div class="user-avatar"></div>
            <span class="user-name"><?= htmlspecialchars($first_name) ?></span>
        </a>
    </div>
        <nav>
            <div class="nav-links">
                <a href="?telegram_id=<?= htmlspecialchars($telegram_id) ?>&page=tasks" title="Задания">
                    <i class="fas fa-tasks"></i>
                    <span>Задания</span>
                </a>
                <a href="?telegram_id=<?= htmlspecialchars($telegram_id) ?>&page=shop" title="Маркетплейс">
                    <i class="fas fa-store"></i>
                    <span>Магазин</span>
                </a>
				<a href="?telegram_id=<?= htmlspecialchars($telegram_id) ?>" title="Главное меню">
    <i class="fas fa-home"></i>
    <span>Меню</span>
</a>

                 <a href="courses.php?telegram_id=<?= htmlspecialchars($telegram_id) ?>&page=rating" title="Курсы">
                    <i class="fas fa-map-signs"></i>
                    <span>Курсы</span>
                </a>
                <a href="?telegram_id=<?= htmlspecialchars($telegram_id) ?>&page=rating" title="Рейтинг">
                    <i class="fas fa-chart-line"></i>
                    <span>Рейтинг</span>
                </a>
            </div>
            <button id="themeToggle" class="theme-toggle" title="Сменить тему">
                <span id="themeIcon">🌜</span>
            </button>
        </nav>
    <?php endif; ?>
<?php else: ?>
    <div class="container">
        <h1>Требуется авторизация</h1>
        <p>Пожалуйста, запустите это приложение через Telegram для доступа к содержимому.</p>
    </div>
<?php endif; ?>

<script>
    const themeToggleBtn = document.getElementById('themeToggle');
    const profileThemeToggleBtn = document.getElementById('profileThemeToggle');
    const body = document.body;
    const themeIcon = document.getElementById('themeIcon');

    const setTheme = (theme) => {
        if (theme === 'dark') {
            body.classList.add('dark');
            themeIcon.textContent = '🌞';
        } else {
            body.classList.remove('dark');
            themeIcon.textContent = '🌜';
        }
        localStorage.setItem('theme', theme);
    };

    const toggleTheme = () => {
        if (body.classList.contains('dark')) {
            setTheme('light');
        } else {
            setTheme('dark');
        }
    };

    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        setTheme(savedTheme);
    } else {
        setTheme('light');
    }

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', toggleTheme);
    }
    if (profileThemeToggleBtn) {
        profileThemeToggleBtn.addEventListener('click', toggleTheme);
    }

    const openModalBtn = document.getElementById('openModalBtn');
    const modal = document.getElementById('nicknameModal');
    const closeModalSpan = document.querySelector('.close-modal');

    if (openModalBtn) {
        openModalBtn.addEventListener('click', () => {
            modal.style.display = 'flex';
        });
    }

    if (closeModalSpan) {
        closeModalSpan.addEventListener('click', () => {
            modal.style.display = 'none';
        });
    }

    window.addEventListener('click', (event) => {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });

    <?php if ($is_authorized && !$is_new_user && $page == 'rating'): ?>
    function fetchRating() {
        fetch("?telegram_id=<?= urlencode($telegram_id) ?>&page=rating&action=fetch_rating")
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const ratingTable = document.getElementById('ratingTable');
                    ratingTable.innerHTML = '';

                    if (data.top_users.length > 0) {
                        data.top_users.forEach((user, index) => {
                            const entry = document.createElement('div');
                            entry.classList.add('rating-entry');

                            const rank = document.createElement('div');
                            rank.classList.add('rank');
                            rank.textContent = index + 1;

                            const nickname = document.createElement('div');
                            nickname.classList.add('nickname');
                            nickname.textContent = user.first_name;

                            const coins = document.createElement('div');
                            coins.classList.add('coins');
                            coins.textContent = user.coin + ' JuniCoins';

                            entry.appendChild(rank);
                            entry.appendChild(nickname);
                            entry.appendChild(coins);

                            ratingTable.appendChild(entry);
                        });
                    } else {
                        const message = document.createElement('p');
                        message.textContent = 'Рейтинг пока пуст.';
                        ratingTable.appendChild(message);
                    }
                } else {
                    console.error(data.message);
                }
            })
            .catch(error => {
                console.error('Ошибка при обновлении рейтинга:', error);
            });
    }

    fetchRating();

    setInterval(fetchRating, 3000);
    <?php endif; ?>

    document.addEventListener('wheel', function(event) {
        if (event.ctrlKey) {
            event.preventDefault();
        }
    }, { passive: false });

    document.addEventListener('keydown', function(event) {
        if ((event.ctrlKey || event.metaKey) && 
            (event.key === '+' || event.key === '-' || event.key === '0')) {
            event.preventDefault();
        }
    }, { passive: false });
</script>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        const imageUrl = 'https://brold.ru/1487.png';

        fetch(imageUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Изображение не доступно');
                }
                return response.blob();
            })
            .then(() => {
                console.log(
                    '%c ',
                    'font-size: 100px; ' +
                    'background: url(' + imageUrl + ') no-repeat; ' +
                    'background-size: contain; ' +
                    'background-position: center; ' +
                    'padding: 50px;'
                );

                console.log('%c Пасхалка 1487', 'font-size: 20px; color: #555;');
            })
            .catch(error => {
                console.error('Ошибка при загрузке изображения:', error);
            });
    });
</script>
</body>
</html>