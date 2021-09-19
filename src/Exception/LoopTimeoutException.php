<?php declare(strict_types=1);
namespace Rikta\TestWebhook\Exception;
use RuntimeException;

/**
 * Exception is thrown when a TimedLoop reaches it's timeout without returning anything
 * @todo: extract (incl. business-logic) into own package
 */
class LoopTimeoutException extends RuntimeException
{}