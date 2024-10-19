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


$course_id = $_GET['course_id'];
if(!$_GET['course_id']) {
    ?>
    <script>window.location.href = 'https://brold.ru/courses.php?telegram_id=<?= $telegram_id; ?>';</script>
    <?php
}
$lessonID = null;
if($_GET['lesson_id']) {
    $lessonID = $_GET['lesson_id'];
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

$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = :course_id");
$stmt->execute(['course_id' => $course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    ?>
    <script>window.location.href = 'https://brold.ru/courses.php';</script>
    <?php
    exit();
}

$stmt = $pdo->prepare("
    SELECT m.id as module_id, m.title as module_title, m.description as module_description, 
           l.lesson_id as lesson_id, l.title as lesson_title 
    FROM course_modules m
    LEFT JOIN lessons l ON m.id = l.module_id
    WHERE m.course_id = :course_id
    ORDER BY m.id, l.order
");
$stmt->execute(['course_id' => $course_id]);
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

$с_modules = [];
$total_lessons = 0;
$total_modules = 0;

foreach ($modules as $module) {
    if (!isset($с_modules[$module['module_id']])) {
        $с_modules[$module['module_id']] = [
            'title' => $module['module_title'],
            'description' => $module['module_description'],
            'lessons' => []
        ];
        $total_modules++;
    }

    if ($module['lesson_id']) {
        $с_modules[$module['module_id']]['lessons'][] = [
            'id' => $module['lesson_id'],
            'title' => $module['lesson_title'],
            'content' => $module['content']
        ];
        $total_lessons++;
    }
}
$stmt = $pdo->prepare("SELECT * FROM course_progress WHERE course_id = :course_id");
$stmt->execute(['course_id' => $course_id]);
$course_progress = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course_progress) {
    $stmt = $pdo->prepare("
        INSERT INTO course_progress (user_id, course_id, completed_modules, completed_lessons, total_modules, total_lessons, completion_status, created_at) 
        VALUES (:user_id, :course_id, 0, 0, $total_modules, $total_lessons, 'in_learning', NOW())
    ");
    $stmt->execute(['user_id' => $user_id, 'course_id' => $course_id]);
    
    $course_progress = [
        'user_id' => $user_id,
        'course_id' => $course_id,
        'completed_modules' => 0,
        'total_modules' => $total_modules,
        'completed_lessons' => 0,
        'total_lessons' => $total_lessons,
        'current_module_id' => 1,
        'current_lesson_id' => 1,
        'last_activity' => null,
        'completion_status' => 'in_learning',
        'created_at' => date('Y-m-d H:i:s')
    ];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
	<?php wp_head(); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php htmlspecialchars($lesson['title']); ?></title>
</head>
<body style="max-width: 480px;margin: 0 auto;">

<?php if ($lessonID):
    $stmt = $pdo->prepare("SELECT * FROM lessons WHERE lesson_id = :lesson_id AND course_id = :course_id");
    $stmt->execute([':lesson_id' => $lessonID, ':course_id' => $course_id]);
    $lesson = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$lesson || $lessonID > $course_progress['total_lessons']){
        ?>
        <script>window.location.href = 'https://brold.ru/course.php/?course_id=<?=$course_id?>';</script>
        <?php
        exit;
    }
    
    $course_lesson_output = '<div class="course">
        <div class="course_details">
            <div class="bar">
                <button onclick="openCourse('.$course_id.')" class="close_btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" class="bi bi-arrow-left-short" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M12 8a.5.5 0 0 1-.5.5H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5a.5.5 0 0 1 .5.5"/>
                    </svg>
                </button>
            </div>
            <h1>'. htmlspecialchars($lesson['title']).'</h1>
            <div class="content">';
    $content = json_decode($lesson['content'], true);

    if (!empty($content)) {
        $outputcontent = '<div class="lesson-content">';

        if (!empty($content['text'])) {
            $outputcontent .= '<div class="lesson-text">';
            $outputcontent .= '<p>' . htmlspecialchars($content['text']) . '</p>';
            $outputcontent .= '</div>';
        }

        if (!empty($content['videolink'])) {
            if (strpos($content['videolink'], 'youtube') !== false || strpos($content['videolink'], 'youtu.be') !== false) {
                $outputcontent .= '<div class="lesson-video">';
                $outputcontent .= '<iframe class="video_frame" width="100%" height="150px" src="' . htmlspecialchars($content['videolink']) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                $outputcontent .= '</div>';
            } else {
                $outputcontent .= '<div class="lesson-video">';
                $outputcontent .= '<video controls>';
                $outputcontent .= '<source src="' . htmlspecialchars($content['videolink']) . '" type="video/mp4">';
                $outputcontent .= 'Ваш браузер не поддерживает видео.';
                $outputcontent .= '</video>';
                $outputcontent .= '</div>';
            }
        }
        if (!empty($content['images'])) {
            $images = explode(',', $content['images']);
            $outputcontent .= '<div class="lesson-images">';
            foreach ($images as $image) {
                $outputcontent .= '<img src="' . htmlspecialchars(trim($image)) . '" alt="Урок изображение" class="lesson-image">';
            }
            $outputcontent .= '</div>';
        }

        $outputcontent .= '</div>';
    } else {
        $outputcontent = '<p>Контент урока отсутствует.</p>';
    }
    $course_lesson_output .= $outputcontent;
    $course_lesson_output .= '
                </div>
            </div>

            <div class="bottom_bar">
                <button class="btn continue_course_btn" onclick="completeCourse('.$course_id.','.$lessonID.')">Следующий урок</button>
            </div>
        </div>
        <div id="imageModal" class="modal">
            <span class="close">&times;</span>
            <div id="image-container">
                <img class="modal-content" id="modalImage">
            </div>
        </div>';
    $course_lesson_output .= '
        </body>
        </html>';
        echo $course_lesson_output;
else:
    $progress_prc = $total_lessons > 0 ? ($course_progress['completed_lessons'] / $course_progress['total_lessons']) * 100 : 0;
?>
    <div class="course">
        <div class="course_details">
            <div class="bar">
                <button onclick="closeCourse()" class="close_btn"><svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" class="bi bi-arrow-left-short" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M12 8a.5.5 0 0 1-.5.5H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5a.5.5 0 0 1 .5.5"/>
                  </svg></button>
                <div class="progress-bar">
                    <div class="progress-indicator" style="width: <?=$progress_prc;?>%;"></div>
                </div>
            </div>
            <h1><?php echo htmlspecialchars($course['title']); ?></h1>
            <p class="description"><?php echo htmlspecialchars($course['description']); ?></p>
        </div>

        <ul class="modules_list">
            <?php foreach ($с_modules as $module_id => $module): ?>
            <li class="module-item" data-id="<?php echo $module_id; ?>">
                <h2><?php echo htmlspecialchars($module['title']); ?></h2>
                <p class="description"><?php echo htmlspecialchars($module['description']); ?></p>
                <?php
        $completed_lessons = 0;
        $total_lessons = count($module['lessons']);
        
        foreach ($module['lessons'] as $lesson) {
            if ($lesson['id'] <= $course_progress['completed_lessons']) {
                $completed_lessons++;
            }
        }

        $progress_prc = $total_lessons > 0 ? ($completed_lessons / $total_lessons) * 100 : 0;
        ?>
                <div class="progress">
                    <h3>Прогресс</h3>
                    <div class="progress-bar">
                        <div class="progress-indicator" style="width: <?php echo $progress_prc; ?>%;"></div>
                    </div>
                </div>
                <button class="btn" onclick="openLessons(<?php echo $module_id; ?>)">Перейти к урокам</button>

                <?php if (!empty($module['lessons'])): ?>
                <ul class="lessons_list" data-module-id="<?php echo $module_id; ?>">
                    <?php foreach ($module['lessons'] as $index => $lesson): ?>
                        <?php
                        $index = $module['lessons'][$index]['id'];
                        $prev_completed = true;
                        $completed_lessons = $course_progress['completed_lessons'];
                        $total_lessons = $course_progress['total_lessons'];
                        $is_disabled = true;
                        $is_completed = false;
                        $is_current = false;

                        if ($index == ($completed_lessons+1)) {
                            $button_text = 'Пройти урок';
                            $is_completed = false;
                            $is_current = true;
                            $is_disabled = false;
                        } elseif ($index <= $completed_lessons) {
                            $button_text = 'Урок пройден';
                            $is_disabled = true;
                            $is_completed = true;
                            $is_current = false;
                        } else {
                            $button_text = 'Пройдите предыдущие уроки';
                            // $button_text .= ' / '.$index;
                            // $button_text .= ' / '.$total_lessons;sss
                            $is_completed = false;
                            $is_current = false;
                            $is_disabled = true;
                        }
                        $prev_completed = $is_completed;
                        ?>

                        <li class="lesson-item" data-id="<?php echo $lesson['id']; ?>">
                            <h3><?php echo htmlspecialchars($lesson['title']); ?></h3>
                            
                            <button 
                                class="btn" 
                                <?php if ($is_disabled): ?> disabled <?php endif; ?>
                                <?php if ($is_current && !$is_disabled): ?> 
                                    onclick="openLesson(<?php echo $course_id; ?>, <?php echo $lesson['id']; ?>)" 
                                <?php endif; ?>>
                                <?php echo $button_text; ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
            <?php
            if($course_progress['completed_lessons'] >= $course_progress['total_lessons']):?>
                <li class="lesson-item completed_course">
                    <h3>Вы успешно прошли весь курс!</h3>
                    
                    <button 
                        class="btn" 
                        onclick="">
                        Получить награду за полное прохождение
                    </button>
                </li>
            <?php endif; ?>
        </ul>

        <div id="imageModal" class="modal">
            <span class="close">&times;</span>
            <div id="image-container">
                <img class="modal-content" id="modalImage">
            </div>
        </div>
    </div>
<?php endif; ?>
    
<?php wp_footer(); ?>

</body>
</html>
