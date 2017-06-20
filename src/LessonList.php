<?php
/**
 * Created by PhpStorm.
 * User: Ezydenias
 * Date: 6/16/2017
 * Time: 10:10
 */

namespace Ezydenias\Vokabeltrainer;

/**
 * Class LessonList
 * Scans a directory for lessons.
 * @package Ezydenias\Vokabeltrainer
 */
class LessonList
{
    /**
     * @var string[]
     */
    private $lessons = [];

    /**
     * LessonList constructor.
     * @param string $lessonDir
     */
    public function __construct($lessonDir)
    {
        $lessonsList = scandir($lessonDir);
        foreach ($lessonsList as $item) {
            if (pathinfo($item, PATHINFO_EXTENSION) == "txt") {
                $item = basename($item, '.txt');
                $this->lessons[] = $item;
            }
        }
    }

    /**
     * Gets the list of lessons.
     * @return string[]
     */
    public function getLessons()
    {
        return $this->lessons;
    }

}