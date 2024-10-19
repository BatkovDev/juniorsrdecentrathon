$(document).ready(function() {
    $('.course .content .lesson-image').on('click', function() {
        const src = $(this).attr('src');
        $('#modalImage').attr('src', src);
        $('#imageModal').css('display', 'block');
    });
    $('.close').on('click', function() {
        $('#imageModal').css('display', 'none');
    });
});

function openCourse(id) {
    const telegramId = getTelegramId();
    if (id) {
        window.location.href = 'https://brold.ru/course.php?course_id=' + id + '&telegram_id=' + telegramId;
    }
}

function closeCourse() {
    const telegramId = getTelegramId();
    window.location.href = 'https://brold.ru/courses.php?telegram_id=' + telegramId;
}

function openLessons(moduleId) {
    var lessonList = $('.lessons_list[data-module-id="' + moduleId + '"]');

    if (lessonList.hasClass('active')) {
        lessonList.removeClass('active');
    } else {
        lessonList.addClass('active');
    }
}

function completeCourse(course_id, lessonID) {
    const telegramId = getTelegramId();
    $.ajax({
        url: 'https://brold.ru/complete-lesson.php',
        type: 'POST',
        data: {
            course_id: course_id,
            lessonID: lessonID,
            telegram_id: telegramId
        },
        success: function(response) {
            var res = typeof response === 'string' ? JSON.parse(response) : response;
            console.log(res);

            if (res.status === 'success') {
                window.location.href = 'https://brold.ru/course.php?course_id=' + course_id + '&lesson_id=' + (lessonID + 1) + '&telegram_id=' + telegramId;
            }
        },
        error: function(xhr, status, error) {
            console.log('Ошибка: ' + error);
            alert('Произошла ошибка при завершении урока.');
        }
    });
}

function openLesson(course_id, lesson_id) {
    const telegramId = getTelegramId();
    if (course_id && lesson_id) {
        window.location.href = 'https://brold.ru/course.php?course_id=' + course_id + '&lesson_id=' + lesson_id + '&telegram_id=' + telegramId;
    }
}

function getTelegramId() {
    const params = new URLSearchParams(window.location.search);
    return params.get('telegram_id');
}
