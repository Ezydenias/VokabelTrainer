<?php
/**
 * Created by PhpStorm.
 * User: Ezydenias
 * Date: 6/13/2017
 * Time: 19:06
 */

const LESSON_DIRECTORY = __DIR__ . '/lessons';
const SCORE_DIRECTORY = __DIR__ . '/scores';
require_once __DIR__ . '/vendor/autoload.php';

$app = new \Silex\Application();

$app->get('/{lesson}/{step}', function ($lesson, $step) {
    $lesson = new \Ezydenias\Vokabeltrainer\Lesson(LESSON_DIRECTORY, SCORE_DIRECTORY, $lesson);
    return new \Symfony\Component\HttpFoundation\JsonResponse($lesson->getStep($step));
});

$app->get('/', function (\Symfony\Component\HttpFoundation\Request $request) {
    return new \Symfony\Component\HttpFoundation\Response('Test');
});

$app->run();