<?php declare(strict_types=1);
namespace Rikta\TestWebhook;

use Rikta\TestWebhook\Exception\NoFreeUserPortException;
use Rikta\TestWebhook\Utils\Output;
use Rikta\TimedLoop\LoopTimeoutException;
use Rikta\TimedLoop\TimedLoop;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Wrapper to start a simple webhook-server and query results.
 * The server automatically searches for a free port and support multiple simultaneous instances.
 */
class WebhookServer
{
    /** Process of the Server */
    private Process $process;

    /** Port $process listens on */
    private int $port;

    /** Temporary directory for requests to this instance */
    private string $tmpDir;

    /**
     * @throws NoFreeUserPortException if the unlikely case occurs that there are no unused ports left...
     * @throws LoopTimeoutException if the server does not come online after a few seconds
     */
    public function __construct()
    {
        $this->port = $this->findUnusedPort();
        $this->tmpDir = Config::TMP_REQUEST_DIR . $this->port;

        if (!is_dir($this->tmpDir) && !mkdir($this->tmpDir, 0700, true) && !is_dir($this->tmpDir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $this->tmpDir));
        }
        $this->clean();

        Output::step('Starting Server on Port ' . $this->port);
        $this->process = Process::fromShellCommandline(
            sprintf('php -S %s:%d -t .', Config::LISTEN_ON, $this->port),
            Config::PUBLIC_PATH,
        );
        $this->process->disableOutput()->start();

        $healthFile = $this->getUrl(Config::HEALTH_FILE);
        Output::step("Waiting for $healthFile to return something");
        TimedLoop::loop(fn () => @file_get_contents($this->getUrl(Config::HEALTH_FILE)));
    }

    /**
     * Stop the process and clean the temporary directory when there are no further references to this instance
     */
    public function __destruct()
    {
        $this->process->stop();
        $this->clean();
    }

    /**
     * Gets the dynamically assigned port
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * Returns an url to $file
     * @param string $file filename (webhook.php or health.php); if not set the webhook is assumed
     * @param string $host hostname for the url, if not set localhost is assumed, but if you interact with an application
     *                     inside another docker-container you would have to adjust this!
     * @return string
     */
    public function getUrl (string $file = Config::WEBHOOK_FILE, string $host = 'localhost'): string {
        /** @noinspection HttpUrlsUsage */
        return sprintf('http://%s:%d/%s', $host, $this->getPort(), $file);
    }

    /**
     * Deletes all files in the temporary directory
     */
    public function clean(): void
    {
        Output::step('Clean all files');
        $this->query()->delete();
    }

    /**
     * Start a query for requests
     */
    public function query(): Query {
        return new Query($this->tmpDir);
    }

    /**
     * Check if a port is unused
     */
    private function isUnusedPort(int $port): bool
    {
            $connection = @fsockopen(Config::LISTEN_ON, $port);

            if (\is_resource($connection))
            {
                fclose($connection);
                return false;
            }
            return true;
    }

    /**
     * search for an unused port inside the configured range
     * @throws NoFreeUserPortException if the unlikely case occurs that there are no unused ports left...
     */
    private function findUnusedPort(): int
    {
        Output::step('Searching unused Port');
        for($port = Config::MIN_PORT; $port <= Config::MAX_PORT; $port++) {
            Output::progress();
            if ($this->isUnusedPort($port)) {
                
                return $port;
            }
        }
        throw new NoFreeUserPortException();
    }
}