<?php
/**
 * Created by PhpStorm.
 * User: Ezydenias
 * Date: 6/13/2017
 * Time: 19:27
 */

namespace Ezydenias\Vokabeltrainer;


class Lesson
{
    private $vocabulary = [];
    private $score = 0;
    private $length = 0;
    private $scoreFilename = '';
    private $lessonFilename = '';
    private $loaded = false;
    private $name = '';

    public function __construct($lessonDir, $scoreDir, $lessonName)
    {
        $this->name = $lessonName;
        $this->scoreFilename = $scoreDir . '/' . $lessonName . '.txt';
        if (file_exists($this->scoreFilename)) {
            $scoreContent = file_get_contents($this->scoreFilename);
            $scoreContent = explode('/', $scoreContent, 2);
            $this->score = intval($scoreContent[0], 10);
            $this->length = intval($scoreContent[1], 10);
        }
        $this->lessonFilename = $lessonDir . '/' . $lessonName . '.txt';
    }

    public function __destruct()
    {
        file_put_contents($this->scoreFilename, $this->score . '/' . $this->length);
    }

    /**
     * @return int
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @param int $score
     * @return Lesson
     */
    public function setScore($score)
    {
        $this->score = $score;
        return $this;
    }

    public function getStep($step, $reverse = false)
    {
        if (!$this->hasStep($step)) {
            throw new \Exception('No such step');
        }
        $step = $this->vocabulary[$step];
        $questionKey = $reverse ? 'language_2' : 'language_1';
        $answerKey = $reverse ? 'language_1' : 'language_2';
        $correctAnswer = $step[$answerKey];
        $allAnswers = array_map(function ($item) use ($answerKey, $correctAnswer) {
            $answer = $item[$answerKey];
            return $answer === $correctAnswer ? null : $answer;
        }, $this->vocabulary);
        $allAnswers = array_filter($allAnswers);
        $allAnswers = array_unique($allAnswers);
        $allAnswers = array_values($allAnswers);
        $answers = array_map(function ($key) use ($allAnswers) {
            return $allAnswers[$key];
        }, array_rand($allAnswers, min(4, count($allAnswers))));
        array_splice($answers, rand(1, count($allAnswers)), 0, $correctAnswer);
        return [
            'question' => $step[$questionKey],
            'answers' => $answers,
        ];
    }

    public function hasStep($step)
    {
        $this->loadLesson();
        return isset($this->vocabulary[$step]);
    }

    private function loadLesson()
    {
        if (!$this->loaded) {
            if (!file_exists($this->lessonFilename)) {
                throw new \Exception('lesson not found');
            }
            $lessonFile = file_get_contents($this->lessonFilename);
            $lessonFile = explode("\n", $lessonFile);
            foreach ($lessonFile as $item) {
                $item = trim($item);
                if ($item) {
                    $item = explode("\t", $item, 2);
                    if (!isset($item[1])) {
                        continue;
                    }
                    $this->vocabulary[] = [
                        'language_1' => $item[0],
                        'language_2' => $item[1],
                    ];
                }
            }
            $this->length = count($this->vocabulary);
            $this->loaded = true;
        }
    }

    public function checkAnswer($step, $answer, $reverse = false)
    {
        if (!$this->hasStep($step)) {
            throw new \Exception('No such step');
        }
        $answerKey = $reverse ? 'language_1' : 'language_2';
        $step = $this->vocabulary[$step];
        return $answer === $step[$answerKey];
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    public function incrementScore()
    {
        $this->score++;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}