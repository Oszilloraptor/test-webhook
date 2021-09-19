<?php declare(strict_types=1);
namespace Rikta\TestWebhook;

/**
 * Static configuration for the Webhook.
 *
 * As we have two completely different entry points (webhook.php and WebhookServer-Class)
 * we cannot rely on an object-instance like we would normally do.
 *
 * @internal
 * @todo: refactor into an adjustable format
 *        idea: create a config when you create a webhook-server.
 *              this file could then be serialized into the tmp-dir of the server-instance
 *              (maybe as tmp/webhook-config/54321.json for Port 54321)
 *              and read from the webhook.php
 */
class Config
{
    /** Debug-Output enabled? */
    public const OUTPUT_ENABLED = false;

    /** Target of Debug-Output */
    public const OUTPUT_TARGET = 'php://stdout';

    /** temporary directory to store webhook requests inside */
    public const TMP_REQUEST_DIR = __DIR__.'/../tmp/webhook-requests/';

    /** host to listen on */
    public const LISTEN_ON = '0.0.0.0';

    /** directory to expose */
    public const PUBLIC_PATH = __DIR__.'/public';

    /** filename of the webhook-file, relative to PUBLIC_PATH */
    public const WEBHOOK_FILE = 'webhook.php';

    /** filename of the health-file, relative to PUBLIC_PATH */
    public const HEALTH_FILE = 'health.php';

    /**
     * Minimum port for the server
     * Should be an ephemeral port (49152 to 65535)
     */
    public const MIN_PORT = 49152;

    /**
     * Maximum port for the server
     * Should be an ephemeral port (49152 to 65535)
     */
    public const MAX_PORT = 65535;
}
