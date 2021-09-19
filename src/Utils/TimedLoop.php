<?php declare(strict_types=1);
namespace Rikta\TestWebhook\Utils;

use Rikta\TestWebhook\Exception\LoopTimeoutException;

class TimedLoop
{
    /**
     * loops a method until its return differs from a given value; or throws an exception when a timeout is reached
     * @param callable $loop function that shall be looped
     * @param mixed $continueReturn value that must be returned by the looped method to keep the loop going
     * @param mixed ...$arguments arguments that shall be passed to the looped method
     * @param int $maxSeconds maximum amount of seconds to run until an exception shall be thrown
     * @param int $retryAfterMicroseconds time in microseconds between and of a call and a new try
     * @todo: extract into own package
     * @return mixed
     */
    public static function loop(callable $loop,
                                $continueReturn = false,
                                array $arguments = [],
                                int $maxSeconds = 60,
                                int $retryAfterMicroseconds = 50000)
    {
        $start = time();
        $end = $start + $maxSeconds;
        while (true) {
            Output::progress();
            $result = ($loop)(...$arguments);
            if ($result !== $continueReturn) {
                
                return $result;
            }
            if (time() > $end) {
                throw new LoopTimeoutException();
            }
            usleep($retryAfterMicroseconds);
        }
    }
}