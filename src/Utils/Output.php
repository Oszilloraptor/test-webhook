<?php declare(strict_types=1);
namespace Rikta\TestWebhook\Utils;

use Rikta\TestWebhook\Config;

/**
 * Simple Static class to provide realtime output, bypassing any output-suppression from PhpUnit
 * @todo: make instanced; would be better to have a different NAME for WebhookServer and webhook.php anyway (could also include port)
 */
class Output
{
    private const ENABLED = Config::OUTPUT_ENABLED;
    private const TARGET = Config::OUTPUT_TARGET;
    private const NAME = 'TestWebhook';
    private const LOOP_MARKER = '.';
    private const NAME_SEPARATOR = ' > ';

    /**
     * Prints something to stdout, bypassing any output-suppression
     * @param string $string
     */
    public static function print(string $string): void
    {
        if (!self::ENABLED) {
            return;
        }

        fwrite(fopen(self::TARGET, 'w'), $string);
    }

    /**
     * Prints the beginning of a new step to a new line, including the name of the current Server
     * @param string $stepName
     */
    public static function step(string $stepName): void
    {
        self::newline();
        self::print( self::NAME . self::NAME_SEPARATOR . $stepName);
    }

    /**
     * Prints a symbol to indicate progress; e.g. in a loop
     */
    public static function progress(): void
    {
        self::print(self::LOOP_MARKER);
    }

    /**
     * Prints a linebreak
     */
    public static function newline(): void
    {
        self::print("\n");
    }
}