<?php declare(strict_types=1);
namespace Rikta\TestWebhook;

/**
 * Static configuration for the Webhook.
 *
 * As we have two completely different entry points (webhook.php and WebhookServer-Class)
 * we cannot rely on an object-instance like we would normally do.
 *
 * @internal
 */
class Config
{
    /** Debug-Output enabled? */
    public const OUTPUT_ENABLED = false;

    /** Target of Debug-Output */
    public const OUTPUT_TARGET = 'php://stdout';

    /** temporary directory to store webhook requests inside */
    public const TMP_REQUEST_DIR = __DIR__.'/../tmp/webhook-requests/';
}
