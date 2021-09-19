<?php declare(strict_types=1);
namespace Rikta\TestWebhook\Exception;
use Exception;

/**
 * Exception is thrown when there is no free port.
 */
class NoFreeUserPortException extends Exception
{
}