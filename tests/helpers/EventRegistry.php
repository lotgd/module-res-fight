<?php
declare(strict_types=1);

namespace LotGD\Module\Res\Fight\Tests\helpers;


use LotGD\Core\Events\EventContext;
use LotGD\Core\Game;

class EventRegistry
{
    public static $registration = [];
    public static $reactions = [];

    public static function reactOn($eventName, $callback)
    {
        if (!isset(self::$reactions[$eventName])) {
            self::$reactions[$eventName] = [];
        }

        array_push(self::$reactions[$eventName], $callback);
    }

    public static function handleEvent(Game $g, EventContext $context): EventContext
    {
        $event = $context->getEvent();

        if (in_array($event, self::$registration)) {
            self::$registration[$event]++;
        } else {
            self::$registration[$event] = 1;
        }

        if (isset(self::$reactions[$event])) {
            foreach (self::$reactions[$event] as $callback) {
                $callback($g, $context);
            }
        }

        return $context;
    }

    public static function reset(): void
    {
        self::$registration = [];
        self::$reactions = [];
    }
}