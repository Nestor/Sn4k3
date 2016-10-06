<?php

namespace Sn4k3\Model;

class Player
{
    const DEFAULT_TICK_INTERVAL = 10;

    /**
     * @var string
     */
    public $hash;

    /**
     * @var string
     */
    public $name;

    /**
     * @var Snake
     */
    public $snake;

    /**
     * @var int
     */
    public $score;

    /**
     * In degree.
     *
     * @var int
     */
    public $angleIntervalOnTick;

    public function __construct(int $angleIntervalOnTick = self::DEFAULT_TICK_INTERVAL)
    {
        $this->angleIntervalOnTick = $angleIntervalOnTick;
    }

    /**
     * @param $direction
     *
     * @return bool
     */
    public function canChangeDirection($direction)
    {
        switch ($direction) {
            case 'up':
                return $this->snake->direction !== 'down';

            case 'down':
                return $this->snake->direction !== 'up';

            case 'left':
                return $this->snake->direction !== 'right';

            case 'right':
                return $this->snake->direction !== 'left';

            default:
                return false;
        }
    }

    /**
     * @param string $direction
     */
    public function changeDirection(string $direction)
    {
        if ($this->canChangeDirection($direction)) {
            $this->snake->direction = $direction;
        }
    }

    public function makeMove()
    {

    }
}
