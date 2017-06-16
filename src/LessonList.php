<?php
/**
 * Created by PhpStorm.
 * User: Ezydenias
 * Date: 6/16/2017
 * Time: 10:10
 */

namespace Ezydenias\Vokabeltrainer;


class LessonList
{

    private $lessons = [];

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
     * @return array
     */
    public function getLessons()
    {
        return $this->lessons;
    }

}