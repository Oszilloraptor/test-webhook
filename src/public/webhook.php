<?php
/**
 * This file is the webhook itself.
 * It serializes the request and stores it in the temporary directory for the incoming port
 */
declare(strict_types=1);

if (file_exists(__DIR__.'/../../vendor/autoload.php')) {
    // autoload-file if the package is in development
    require __DIR__.'/../../vendor/autoload.php';
} else {
    // autoload-file if the package is itself in the vendor-folder
    /** @noinspection PhpIncludeInspection */
    require __DIR__.'/../../../../autoload.php';
}

use Jasny\HttpMessage\ServerRequest;
use Rikta\TestWebhook\Config;
use Rikta\TestWebhook\Utils\Output;

// create a unique id for the request
$uniqueId = uniqid('request-', true);
$logName = $uniqueId . '@' . $_SERVER['SERVER_PORT'];

Output::step('Received ' . $logName);
// Creates a PSR-7 compatible ServerRequest from the current environment
$request = (new ServerRequest())->withGlobalEnvironment();

Output::step('Serialize ' . $logName);
// Serializes the request. As the Serializer will kill the resource to the body we have to store the content separately
$serialized = serialize([$request, $request->getBody()->getContents()]);

// stores the serialized content in a temporary file
$file = Config::TMP_REQUEST_DIR . $_SERVER['SERVER_PORT'] . '/' . $uniqueId;
file_put_contents($file, $serialized);
Output::step('Stored ' . $logName . ' in ' . $file);
