<?php
/**
 * Created by PhpStorm.
 * User: Ezydenias
 * Date: 6/13/2017
 * Time: 19:06
 */

/**
 * Constants for settings.
 */
const LESSON_DIRECTORY = __DIR__ . '/lessons';      // Directory with lesson files (must be writable)
const SCORE_DIRECTORY = __DIR__ . '/scores';        // Directory with score files (must be writable)
const TEMPLATE_DIRECTORY = __DIR__ . '/templates';  // Directory with templates
const SERVER_PREFIX = '';                           // Prefix for urls

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Load a template and render it into a buffer.
 * @param string $templateName
 * @param mixed[] $variables
 * @return string
 */
function render($templateName, $variables)
{
    extract($variables);
    ob_start();
    require TEMPLATE_DIRECTORY . '/' . $templateName;
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}

/**
 * Create the server app.
 */
$app = new \Silex\Application();

/**
 * Handler for the last step in a lesson.
 */
$app->get('/{lessonName}/finish', function ($lessonName) {
    $lesson = new \Ezydenias\Vokabeltrainer\Lesson(LESSON_DIRECTORY, SCORE_DIRECTORY, $lessonName);
    $vars = [
        'lesson' => $lesson,
        'lessonName' => $lessonName,
        'next' => "/",
    ];
    return new \Symfony\Component\HttpFoundation\Response(render('lesson-end.phtml', $vars));
});

/**
 * Handler for a single lesson step (before selection).
 */
$app->get('/{lessonName}/{step}/{reverse}', function ($lessonName, $step, $reverse) {
    $lesson = new \Ezydenias\Vokabeltrainer\Lesson(LESSON_DIRECTORY, SCORE_DIRECTORY, $lessonName);
    // Very first step? Let's reset the score.
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

/**
 * Handler for a single lesson step (after selection).
 */
$app->post('/{lessonName}/{step}/{reverse}', function (\Symfony\Component\HttpFoundation\Request $request, $lessonName, $step, $reverse) {
    $lesson = new \Ezydenias\Vokabeltrainer\Lesson(LESSON_DIRECTORY, SCORE_DIRECTORY, $lessonName);
    $givenAnswer = $request->get('answer');

    // Check answer and increment score
    $answerIsCorrect = $lesson->checkAnswer($step, $givenAnswer, $reverse);
    if ($answerIsCorrect) {
        $lesson->incrementScore();
    }

    $question = $lesson->getStep($step, $reverse);
    // We override the answers, because we'll otherwise tend to get a different set of answers, with only the
    // correct answer garantueed to be included
    $availableAnswers = explode('|', $request->get('available'));
    $question['answers'] = $availableAnswers;

    $next = $step + 1;
    // No next step? Go to finish.
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

/*
 * Handler for the report function.
 */
$app->get('/report', function () {
    $lessons = new \Ezydenias\Vokabeltrainer\LessonList(LESSON_DIRECTORY);
    $vars = [
        'lessons' => array_map(function ($lesson) {
            return new \Ezydenias\Vokabeltrainer\Lesson(LESSON_DIRECTORY, SCORE_DIRECTORY, $lesson);
        }, $lessons->getLessons()),
    ];
    return new \Symfony\Component\HttpFoundation\Response(render('report.phtml', $vars));
});

/**
 * Handler for the upload function (before file is send).
 */
$app->get('/upload', function () {
    return new \Symfony\Component\HttpFoundation\Response(render('upload.phtml', [
        'filename' => false,
        'error' => false,
    ]));
});

/**
 * Handler for the upload function (after file is send).
 */
$app->post('/upload', function (\Symfony\Component\HttpFoundation\Request $request) {
    $error = false;
    /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
    $file = $request->files->get('lesson-file');

    // Clean filename
    $filename = strip_tags($file->getClientOriginalName());
    $filename = str_replace(['/', '\\', "\t", "\n", '<', '>', ':', '*', ':', '"', '|'], ' ', $filename);

    $file->move(LESSON_DIRECTORY, $filename);

    /**
     * We try to load the lesson once, so we
     * a) Get a score file
     * b) Get an exception if there was some problem with moving the file or bad contents
     */
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

/**
 * Handler for the main page.
 */
$app->get('/', function () {
    $lessons = new \Ezydenias\Vokabeltrainer\LessonList(LESSON_DIRECTORY);
    $vars = [
        'lessons' => $lessons->getLessons(),
    ];
    return new \Symfony\Component\HttpFoundation\Response(render('main.phtml', $vars));
});

$app->run();