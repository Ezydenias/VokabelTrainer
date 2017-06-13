<?php
/**
 * Created by PhpStorm.
 * User: Ezydenias
 * Date: 6/13/2017
 * Time: 19:06
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = new \Silex\Application();

$app->get('/', function (\Symfony\Component\HttpFoundation\Request $request) {
    return new \Symfony\Component\HttpFoundation\Response('Test');
});

$app->run();