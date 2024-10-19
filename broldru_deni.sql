-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: localhost:3306
-- Время создания: Окт 19 2024 г., 23:58
-- Версия сервера: 10.6.19-MariaDB
-- Версия PHP: 8.3.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `broldru_deni`
--

-- --------------------------------------------------------

--
-- Структура таблицы `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `courses`
--

INSERT INTO `courses` (`id`, `title`, `description`, `created_at`) VALUES
(4, 'Основы программирования', 'Курс по Основам программирования предназначен для новичков, которые хотят освоить базовые концепции программирования. Студенты познакомятся с основами алгоритмизации, синтаксисом популярных языков программирования, методами решения задач и основами проектирования. По окончании курса учащиеся смогут писать простые программы и понимать основные концепции программирования, такие как переменные, операторы, функции и структуры данных.', '2024-10-19 08:29:28');

-- --------------------------------------------------------

--
-- Структура таблицы `course_modules`
--

CREATE TABLE `course_modules` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `order` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `course_modules`
--

INSERT INTO `course_modules` (`id`, `module_id`, `course_id`, `title`, `description`, `order`, `created_at`) VALUES
(12, 1, 4, 'Введение в программирование', 'Этот модуль знакомит студентов с основными концепциями программирования, его значением и различными языками программирования. Студенты узнают, что такое алгоритм и как его применять для решения задач.', 1, '2024-10-19 08:29:28'),
(13, 2, 4, 'Основы алгоритмизации', 'В этом модуле студенты изучат основные алгоритмические структуры, такие как последовательность, выбор и цикл. Также будет рассмотрен принцип создания алгоритмов.', 2, '2024-10-19 08:29:28'),
(14, 3, 4, 'Синтаксис языков программирования', 'Студенты познакомятся с синтаксисом популярных языков программирования, таких как Python, Java и C++. Они узнают, как использовать основные конструкции и структуры.', 3, '2024-10-19 08:29:28'),
(15, 4, 4, 'Функции и структуры данных', 'В этом модуле студенты научатся создавать и использовать функции, а также работать с основными структурами данных, такими как массивы и списки.', 4, '2024-10-19 08:29:28'),
(16, 5, 4, 'Практическое применение', 'Завершение курса будет сосредоточено на практике. Студенты применят свои знания для решения реальных задач и создания простых программ.', 5, '2024-10-19 08:29:28');

-- --------------------------------------------------------

--
-- Структура таблицы `course_progress`
--

CREATE TABLE `course_progress` (
  `progress_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `completed_modules` int(11) DEFAULT 0,
  `total_modules` int(11) NOT NULL,
  `completed_lessons` int(11) DEFAULT 0,
  `total_lessons` int(11) NOT NULL,
  `current_module_id` int(11) DEFAULT NULL,
  `current_lesson_id` int(11) DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completion_status` enum('not_started','in_learning','completed') DEFAULT 'in_learning',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `course_progress`
--

INSERT INTO `course_progress` (`progress_id`, `user_id`, `course_id`, `completed_modules`, `total_modules`, `completed_lessons`, `total_lessons`, `current_module_id`, `current_lesson_id`, `last_activity`, `completion_status`, `created_at`) VALUES
(172, 2147483647, 4, 0, 10, 0, 50, NULL, NULL, '2024-10-19 18:31:40', 'in_learning', '2024-10-19 18:31:40'),
(173, 2147483647, 4, 0, 5, 0, 10, NULL, NULL, '2024-10-19 18:31:40', 'in_learning', '2024-10-19 18:31:40'),
(174, 2147483647, 4, 0, 10, 0, 50, NULL, NULL, '2024-10-19 18:31:40', 'in_learning', '2024-10-19 18:31:40'),
(175, 2147483647, 4, 0, 10, 0, 50, NULL, NULL, '2024-10-19 18:31:41', 'in_learning', '2024-10-19 18:31:41'),
(176, 2147483647, 4, 0, 10, 0, 50, NULL, NULL, '2024-10-19 18:31:41', 'in_learning', '2024-10-19 18:31:41'),
(177, 2147483647, 4, 0, 10, 0, 50, NULL, NULL, '2024-10-19 18:31:41', 'in_learning', '2024-10-19 18:31:41'),
(178, 2147483647, 4, 0, 10, 0, 50, NULL, NULL, '2024-10-19 18:42:39', 'in_learning', '2024-10-19 18:42:39'),
(179, 2147483647, 4, 0, 5, 0, 10, NULL, NULL, '2024-10-19 18:42:39', 'in_learning', '2024-10-19 18:42:39'),
(180, 2147483647, 4, 0, 5, 0, 10, NULL, NULL, '2024-10-19 18:42:42', 'in_learning', '2024-10-19 18:42:42'),
(181, 2147483647, 4, 0, 5, 0, 10, NULL, NULL, '2024-10-19 18:42:46', 'in_learning', '2024-10-19 18:42:46'),
(182, 2147483647, 4, 0, 5, 0, 10, NULL, NULL, '2024-10-19 18:42:50', 'in_learning', '2024-10-19 18:42:50'),
(183, 500419964, 4, 0, 5, 0, 10, NULL, NULL, '2024-10-19 18:46:33', 'in_learning', '2024-10-19 18:46:33');

-- --------------------------------------------------------

--
-- Структура таблицы `lessons`
--

CREATE TABLE `lessons` (
  `id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`content`)),
  `order` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `lessons`
--

INSERT INTO `lessons` (`id`, `lesson_id`, `module_id`, `course_id`, `title`, `content`, `order`, `created_at`) VALUES
(41, 1, 12, 4, 'Что такое программирование?', '{\"text\": \"Программирование - это процесс создания программного обеспечения, который включает в себя написание, тестирование и отладку кода. В этом уроке мы рассмотрим основные понятия и термины, связанные с программированием. Также узнаем, какие языки программирования существуют и для чего они применяются.\", \"videolink\": \"\", \"images\": \"\"}', 1, '2024-10-19 08:29:28'),
(42, 2, 12, 4, 'Алгоритмы и их важность', '{\"text\": \"Алгоритм - это последовательность действий, необходимых для решения задачи. В этом уроке мы рассмотрим, что такое алгоритмы, как их создавать и почему они важны для программиста.\", \"videolink\": \"\", \"images\": \"\"}', 2, '2024-10-19 08:29:28'),
(43, 3, 13, 4, 'Условные конструкции', '{\"text\": \"Условные конструкции позволяют выполнять разные действия в зависимости от условий. В этом уроке мы изучим конструкции if, else и switch, а также примеры их применения.\", \"videolink\": \"\", \"images\": \"\"}', 1, '2024-10-19 08:29:28'),
(44, 4, 13, 4, 'Циклы', '{\"text\": \"Циклы используются для повторения действий. Мы изучим различные типы циклов, такие как for и while, и их использование в программировании.\", \"videolink\": \"\", \"images\": \"\"}', 2, '2024-10-19 08:29:28'),
(45, 5, 14, 4, 'Основы синтаксиса Python', '{\"text\": \"Python - это язык программирования, который используется для разработки веб-приложений, анализа данных и многого другого. Мы изучим основные конструкции синтаксиса и простые примеры программ.\", \"videolink\": \"\", \"images\": \"\"}', 1, '2024-10-19 08:29:28'),
(46, 6, 14, 4, 'Синтаксис Java', '{\"text\": \"Java - это универсальный язык программирования, который используется в различных областях. Мы рассмотрим основы синтаксиса Java и создадим простую программу.\", \"videolink\": \"\", \"images\": \"\"}', 2, '2024-10-19 08:29:28'),
(47, 7, 15, 4, 'Что такое функции?', '{\"text\": \"Функции - это блоки кода, которые могут быть вызваны из других частей программы. Мы изучим, как создавать и использовать функции для упрощения кода.\", \"videolink\": \"\", \"images\": \"\"}', 1, '2024-10-19 08:29:28'),
(48, 8, 15, 4, 'Структуры данных', '{\"text\": \"Структуры данных - это способы организации и хранения данных в компьютере. Мы рассмотрим массивы, списки и их использование в программировании.\", \"videolink\": \"\", \"images\": \"\"}', 2, '2024-10-19 08:29:28'),
(49, 9, 16, 4, 'Создание простой программы', '{\"text\": \"В этом уроке студенты применят все знания, которые они получили, для создания своей первой программы. Мы рассмотрим, как планировать и реализовать программу от идеи до кода.\", \"videolink\": \"\", \"images\": \"\"}', 1, '2024-10-19 08:29:28'),
(50, 10, 16, 4, 'Обсуждение и обратная связь', '{\"text\": \"После завершения программы мы проведем обсуждение, где студенты смогут поделиться своим опытом и получить обратную связь о своих проектах.\", \"videolink\": \"\", \"images\": \"\"}', 2, '2024-10-19 08:29:28');

-- --------------------------------------------------------

--
-- Структура таблицы `task1`
--

CREATE TABLE `task1` (
  `id` int(11) NOT NULL,
  `task_description` varchar(255) NOT NULL,
  `result_true` varchar(50) NOT NULL,
  `result_false` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `task1`
--

INSERT INTO `task1` (`id`, `task_description`, `result_true`, `result_false`) VALUES
(1, 'Создайте базовую структуру веб-страницы с использованием HTML.', 'Правильно.', 'Не правильно. Нужно поработать над кодом.');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `idtelegram` bigint(20) NOT NULL,
  `coin` int(11) DEFAULT 0,
  `task1` int(11) DEFAULT 0,
  `task2` int(11) DEFAULT 0,
  `AI` varchar(255) DEFAULT NULL,
  `coinsocial` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `first_name`, `idtelegram`, `coin`, `task1`, `task2`, `AI`, `coinsocial`) VALUES
(4, 'deni', 6455709242, 3000, 0, 0, 'yes', 0),
(5, 'всвв', 1300836384, 300, 1, 1, NULL, 0),
(6, 'Дядя Толя ', 6346387904, 0, 0, 0, NULL, 0),
(7, 'zdcdzcz', 100836384, 300, 1, 0, NULL, 0),
(8, 'свсвсв', 10836384, 3458, 1, 0, NULL, 0),
(9, 'фысфсв', 1083684, 3000, 1, 0, NULL, 0),
(10, 'Erasyl', 5962024991, 601, 1, 1, NULL, 0),
(11, 'Abylaikhan', 500419964, 27000, 1, 0, 'yes', 0),
(12, 'efewfew', 1083682, 300, 1, 0, NULL, 0),
(13, 'ссфсфы', 130083384, 900, 1, 1, NULL, 0),
(14, 'bqtk0vdev', 1338712698, 0, 0, 0, NULL, 0),
(15, 'Ансар', 13003384, 300, 1, 0, NULL, 0),
(16, 'фывапролдж', 1300338, 300, 1, 0, NULL, 0),
(17, 'Киборг228148812345изигоиуабутиктоклайккроликясамыйгрязныйзаяц', 130031138, 300, 1, 0, NULL, 0),
(18, 'фывапролдж', 1301031138, 0, 0, 0, NULL, 0),
(19, 'ывап', 100836184, 0, 0, 0, NULL, 0);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `course_modules`
--
ALTER TABLE `course_modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Индексы таблицы `course_progress`
--
ALTER TABLE `course_progress`
  ADD PRIMARY KEY (`progress_id`),
  ADD KEY `current_module_id` (`current_module_id`),
  ADD KEY `current_lesson_id` (`current_lesson_id`);

--
-- Индексы таблицы `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`);

--
-- Индексы таблицы `task1`
--
ALTER TABLE `task1`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `course_modules`
--
ALTER TABLE `course_modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT для таблицы `course_progress`
--
ALTER TABLE `course_progress`
  MODIFY `progress_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=184;

--
-- AUTO_INCREMENT для таблицы `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT для таблицы `task1`
--
ALTER TABLE `task1`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `course_progress`
--
ALTER TABLE `course_progress`
  ADD CONSTRAINT `course_progress_ibfk_1` FOREIGN KEY (`current_module_id`) REFERENCES `course_modules` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `course_progress_ibfk_2` FOREIGN KEY (`current_lesson_id`) REFERENCES `lessons` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `lessons`
--
ALTER TABLE `lessons`
  ADD CONSTRAINT `lessons_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `course_modules` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
