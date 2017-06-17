<?php
/**
 * Created by PhpStorm.
 * User: Ezydenias
 * Date: 6/13/2017
 * Time: 19:06
 */

const LESSON_DIRECTORY = __DIR__ . '/lessons';
const SCORE_DIRECTORY = __DIR__ . '/scores';
const TEMPLATE_DIRECTORY = __DIR__ . '/templates';
const SERVER_PREFIX = '';
require_once __DIR__ . '/vendor/autoload.php';

function render($templateName, $variables)
{
    extract($variables);
    ob_start();
    require TEMPLATE_DIRECTORY . '/' . $templateName;
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}

$app = new \Silex\Application();

$app->get('/{lessonName}/finish', function ($lessonName) {
    $lesson = new \Ezydenias\Vokabeltrainer\Lesson(LESSON_DIRECTORY, SCORE_DIRECTORY, $lessonName);
    $vars = [
        'lesson' => $lesson,
        'lessonName' => $lessonName,
        'next' => "/",
    ];
    return new \Symfony\Component\HttpFoundation\Response(render('lesson-end.phtml', $vars));
});

$app->get('/{lessonName}/{step}/{reverse}', function ($lessonName, $step, $reverse) {
    $lesson = new \Ezydenias\Vokabeltrainer\Lesson(LESSON_DIRECTORY, SCORE_DIRECTORY, $lessonName);
    if ($step == 0) {
        $lesson->setScore(0);
    }
    $question = $lesson->getStep($step, $reverse);
    $vars = [
        'lesson' => $lesson,
        'question' => $question,
        'step' => $step,
        'lessonName' => $lessonName,
        'action' => "/{$lessonName}/{$step}",
        'reverse' => (bool)$reverse,
    ];
    return new \Symfony\Component\HttpFoundation\Response(render('lesson-step.phtml', $vars));
})
    ->value('reverse', false);

$app->post('/{lessonName}/{step}/{reverse}', function (\Symfony\Component\HttpFoundation\Request $request, $lessonName, $step, $reverse) {
    $lesson = new \Ezydenias\Vokabeltrainer\Lesson(LESSON_DIRECTORY, SCORE_DIRECTORY, $lessonName);
    $availableAnswers = explode('|', $request->get('available'));
    $givenAnswer = $request->get('answer');
    $answerIsCorrect = $lesson->checkAnswer($step, $givenAnswer, $reverse);
    if ($answerIsCorrect) {
        $lesson->incrementScore();
    }
    $question = $lesson->getStep($step, $reverse);
    $question['answers'] = $availableAnswers;

    $next = $step + 1;
    if (!$lesson->hasStep($next)) {
        $next = 'finish';
        $reverse = false;
    }

    $vars = [
        'lesson' => $lesson,
        'question' => $question,
        'givenAnswer' => $givenAnswer,
        'step' => $step,
        'lessonName' => $lessonName,
        'next' => "/{$lessonName}/{$next}",
        'isCorrect' => $answerIsCorrect,
        'reverse' => (bool)$reverse,
    ];
    return new \Symfony\Component\HttpFoundation\Response(render('lesson-step-check.phtml', $vars));
})
    ->value('reverse', false);

$app->get('/report', function () {
    $lessons = new \Ezydenias\Vokabeltrainer\LessonList(LESSON_DIRECTORY);
    $vars = [
        'lessons' => array_map(function ($lesson) {
            return new \Ezydenias\Vokabeltrainer\Lesson(LESSON_DIRECTORY, SCORE_DIRECTORY, $lesson);
        }, $lessons->getLessons()),
    ];
    return new \Symfony\Component\HttpFoundation\Response(render('report.phtml', $vars));
});

$app->get('/upload', function () {
    return new \Symfony\Component\HttpFoundation\Response(render('upload.phtml', [
        'filename' => false,
        'error' => false,
    ]));
});

$app->post('/upload', function (\Symfony\Component\HttpFoundation\Request $request) {
    $error = false;
    /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
    $file = $request->files->get('lesson-file');
    $filename = strip_tags($file->getClientOriginalName());
    $filename = str_replace(['/', '\\', "\t", "\n", '<', '>', ':', '*', ':', '"', '|'], ' ', $filename);
    $file->move(LESSON_DIRECTORY, $filename);
    try {
        $lesson = new \Ezydenias\Vokabeltrainer\Lesson(LESSON_DIRECTORY, SCORE_DIRECTORY, basename($filename, '.txt'));
        $lesson->loadLesson();
    } catch (Exception $e) {
        unset($lesson);
        $error = $e->getMessage();
        if (file_exists(LESSON_DIRECTORY . '/' . $filename)) {
            unlink(LESSON_DIRECTORY . '/' . $filename);
        }
        if (file_exists(SCORE_DIRECTORY . '/' . $filename)) {
            unlink(SCORE_DIRECTORY . '/' . $filename);
        }
    }
    return new \Symfony\Component\HttpFoundation\Response(render('upload.phtml', [
        'filename' => $filename,
        'error' => $error,
    ]));
});

$app->get('/', function () {
    $lessons = new \Ezydenias\Vokabeltrainer\LessonList(LESSON_DIRECTORY);
    $vars = [
        'lessons' => $lessons->getLessons(),
    ];
    return new \Symfony\Component\HttpFoundation\Response(render('main.phtml', $vars));
});

$app->run();