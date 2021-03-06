<?php

namespace Sn4k3;

use Evenement\EventEmitterTrait;
use React\EventLoop\LoopInterface;
use Sn4k3\Geometry\Circle;
use Sn4k3\Geometry\Point;
use Sn4k3\Model\Food;
use Sn4k3\Model\Map;
use Sn4k3\Model\Player;

class Game
{
    const DEFAULT_TICK_INTERVAL = 25;
    const DEFAULT_FOOD_APPEARANCE_TICK = 40;

    const EVENT_TICK = 'event_tick';
    const EVENT_COLLISION = 'event_collision';

    use EventEmitterTrait;

    /**
     * @var Map
     */
    private $map;

    /**
     * In milliseconds.
     *
     * @var int
     */
    private $tickInterval;

    /**
     * @var Player[]
     */
    private $players = [];

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var Event[]
     */
    private $awaitingEvents = [];

    /**
     * @var bool
     */
    private $isRunning = false;

    /**
     * @var int
     */
    private $foodTicks = self::DEFAULT_FOOD_APPEARANCE_TICK;

    /**
     * @param LoopInterface $loop
     * @param int|null $tickInterval
     * @param Map $map
     */
    public function __construct(LoopInterface $loop, int $tickInterval = self::DEFAULT_TICK_INTERVAL, Map $map = null)
    {
        $this->loop = $loop;
        $this->map = $map ?? new Map();
        $this->tickInterval = $tickInterval;
    }

    /**
     * Run the loop for the game to start.
     */
    public function run()
    {
        if ($this->isRunning) {
            throw new \LogicException('Game is already running');
        }

        // Execute $this->tick() on every tick interval
        $this->loop->addPeriodicTimer($this->tickInterval / 1000, [$this, 'tick']);

        $this->isRunning = true;
    }

    /**
     * Execute every action while ticking:
     */
    public function tick()
    {
        // Execute all events in the queue.
        while ($event = array_shift($this->awaitingEvents)) {
            // Change the keyPress status on each event.
            $event->getPlayer()->keyPressed = $event->isKeyPressed();
            $event->getPlayer()->snake->direction = $event->getDirection();
        }

        // Create food elements in the field
        if ($this->foodTicks <= 0 && count($this->players)) {
            /** @var Player $randomPlayer */
            $randomPlayer = $this->players[array_rand($this->players)];

            $snakeHead = $randomPlayer->snake->getHead();

            $randomRange = function($start, $end, $min, $max) {
                while ($random = rand($start, $end)) {
                    if ($random < $min || $random > $max) {
                        return $random;
                    }
                }
            };

            $newPoint = new Point(
                $snakeHead->centerPoint->x + ($x = $randomRange(-500, 500, -100, 100)),
                $snakeHead->centerPoint->y + ($y = $randomRange(-500, 500, -100, 100))
            );

            $foodValueAndRadius = rand(50, 150);
            $food = new Food(new Circle($newPoint, $foodValueAndRadius), ceil($foodValueAndRadius / 5));
            $food->lifetime = rand(100, 500);

            $this->map->foods[] = $food;

            $this->foodTicks = self::DEFAULT_FOOD_APPEARANCE_TICK;
        } else {
            $this->foodTicks--;
        }

        foreach ($this->map->foods as $food) {
            $food->lifetime--;

            if ($food->lifetime === 0) {
                unset($this->map->foods[array_search($food, $this->map->foods)]);
                $food = null;
            }
        }

        // Remove all players that have lost their snake.
        foreach ($this->players as $k => $player) {
            if ($player->snake->destroyed) {
                echo sprintf(
                    'Player %s has lost!',
                    $player->name
                );

                // Make sure player is correctly destroyed, to free memory.
                $player->destroy();
                $player = null;
                unset($this->players[$k]);
            }
        }

        // Move each snake.
        foreach ($this->players as $player) {
            $movementSuccessful = $player->snake->move();

            if (!$movementSuccessful) {
                $this->emit(self::EVENT_COLLISION, [$player]);
                $player->snake->destroyed = true;
            }
        }

        $this->emit(self::EVENT_TICK, [$this]);
    }

    /**
     * @param Event $event
     */
    public function addEvent(Event $event)
    {
        $this->awaitingEvents[] = $event;
    }

    /**
     * @param string $name
     * @param string $direction
     */
    public function changeDirection(string $name, string $direction)
    {
        $player = $this->getPlayerByName($name);

        if (!$player->canChangeDirection($direction)) {
            throw new \InvalidArgumentException('Come on, you are snek, you cannot move towards your back.');
        }

        $player->changeDirection($direction);
    }

    /**
     * @param string $name
     */
    public function initializePlayer(string $name)
    {
        $playerExists = $this->getPlayerByName($name, false);

        if ($playerExists) {
            return;
        }

        $player = new Player($this->map);
        $player->hash = substr(md5(random_bytes(64)), 0, 16);
        $player->name = $name;

        $this->players[$player->hash] = $player;
    }

    /**
     * @param string $name
     * @param bool $exceptional
     *
     * @return Player
     */
    public function getPlayerByName(string $name, bool $exceptional = true)
    {
        foreach ($this->players as $player) {
            if ($player->name === $name) {
                return $player;
            }
        }

        if ($exceptional) {
            throw new \InvalidArgumentException('No such player');
        }
    }

    /**
     * @param $hash
     *
     * @return Player
     */
    public function getPlayerByHash(string $hash): Player
    {
        if (array_key_exists($hash, $this->players)) {
            return $this->players[$hash];
        }

        throw new \InvalidArgumentException('No such player');
    }

    /**
     * @return Player[]
     */
    public function getPlayers()
    {
        return $this->players;
    }

    /**
     * @return Map
     */
    public function getMap()
    {
        return $this->map;
    }
}
