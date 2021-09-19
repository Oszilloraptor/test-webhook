<?php declare(strict_types=1);
namespace Rikta\TestWebhook;

/**
 * Static configuration for the Webhook.
 */
class Config
{
    /** Debug-Output enabled? */
    public const OUTPUT_ENABLED = false;

    /** Target of Debug-Output */
    public const OUTPUT_TARGET = 'php://stdout';
}
