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

$app->get('/', function (\Symfony\Component\HttpFoundation\Request $request) {
    return new \Symfony\Component\HttpFoundation\Response('Test');
});

$app->run();