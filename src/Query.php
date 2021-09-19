<?php declare(strict_types=1);
namespace Rikta\TestWebhook;
use Psr\Http\Message\ServerRequestInterface;
use Rikta\TestWebhook\Utils\Output;
use Rikta\TestWebhook\Utils\StreamDummy;
use Rikta\TimedLoop\LoopTimeoutException;
use Rikta\TimedLoop\TimedLoop;
use function array_slice;
use function count;

/**
 * QueryBuilder for ServerRequestInterfaces in the temporary folder.
 * Provides methods to set limits, filters and sorting-method and subsequently get all requests.
 * @todo: create abstraction in a separate package with general query-logic; e.g. sorting, first, last, etc.
 */
class Query
{
    /** directory of the serialized requests */
    private string $dir;

    /**
     * limits the result to the first/last $slice elements
     *  positive = get first $slice
     *         0 = don't slice
     *  negative = get last $slice
     */
    private int $slice = 0;

    /** @var callable function to sort the ServerRequestInterfaces */
    private $sortFunction;

    /**
     * @var callable[] callbacks that filters an array<ServerRequestInterface>.
     *                 if the callback returns true, the request will stay
     *                 if the callback returns false, the request gets filtered out.
     */
    private array $filters = [];

    public function __construct(string $dir)
    {
        $this->dir = $dir;
    }
    public function sort(?callable $sortFunction = null): self
    {
        /** @noinspection ProperNullCoalescingOperatorUsageInspection */
        $this->sortFunction = $sortFunction ?? ['self', 'sortRequestsChronologically'];
        return $this;
    }
    public function filter(callable $callable): self { $this->filters[] = $callable; return $this; }

    /**
     * blocks until $amount matching requests are present
     * @throws LoopTimeoutException if $maxSeconds have passed without $amount of matching requests
     */
    public function waitForMatchingRequests(int $amount = 1, $maxSeconds = 10): self
    {
        (new TimedLoop(fn () => $this->count() >= $amount))
            ->forMaximumSeconds($maxSeconds)
            ->invoke();
        return $this;
    }

    /**
     * limits the result to the first $n requests
     */
    public function first($n = 1): self {
        $this->slice = $n;
        return $this;
    }

    /**
     * limits the result to the last $n requests
     */
    public function last($n = 1): self {
        $this->slice = -$n;
        return $this;
    }

    /**
     * deletes the files of all matching requests
     */
    public function delete(): void {
        Output::step('Deleting all files matching the current query');
        foreach (array_keys($this->getRequestsFromCurrentQuery()) as $file) {
            Output::progress();
            unlink($this->dir . '/' . $file);
        }

    }

    /**
     * returns the amount of matching requests
     */
    public function count(): int {
        return \count($this->getRequestsFromCurrentQuery());
    }

    /**
     * @return ServerRequestInterface[]
     */
    public function get(): array {
        return array_values($this->getRequestsFromCurrentQuery());
    }

    /**
     * returns requests after applying previously added filters and sorting
     * @return array<string, ServerRequestInterface>
     */
    private function getRequestsFromCurrentQuery(): array {
        $requests = $this->getRequestsFromFiles();
        $requests = $this->filterRequests($requests);
        if ($this->sortFunction !== null) {
            uasort($requests, $this->sortFunction);
        }
        return $this->sliceRequests($requests);
    }

    /**
     * filters given requests with all currently set filters
     * @param array<string, ServerRequestInterface> $requests
     * @return array<string, ServerRequestInterface>
     */
    private function filterRequests(array $requests): array {
        foreach ($this->filters as $filter) {
            $requests = array_filter($requests, $filter);
        }
        return $requests;
    }

    /**
     * slices the given requests according to $this->slice
     * @param array<string, ServerRequestInterface> $requests
     * @return array<string, ServerRequestInterface>
     */
    private function sliceRequests(array $requests): array {
        if ($this->slice > 0) {
            return \array_slice($requests, 0, $this->slice, true);
        }

        if ($this->slice < 0) {
            return \array_slice($requests, $this->slice, null,true);
        }

        return $requests;
    }

    /**
     * unserializes all ServerRequestInterfaces stored in the temporary folder for this server
     * @return array<string, ServerRequestInterface>
     */
    private function getRequestsFromFiles(): array
    {
        $paths = array_diff(scandir($this->dir), ['..', '.']);
        $result = [];
        foreach ($paths as $path) {
            $unserialized = unserialize(
                file_get_contents($this->dir . '/' . $path),
                ['allowed_classes' => true]
            );

            /**
             * @var ServerRequestInterface $request
             * @var string $content
             */
            [$request, $content] = $unserialized;

            $result[$path] = $request->withBody(new StreamDummy($content));
        }
        return $result;
    }

    /**
     * Comparison-function for ServerRequestInterfaces
     */
    private static function sortRequestsChronologically(ServerRequestInterface $a, ServerRequestInterface $b): int {
        if ($a->getServerParams()['REQUEST_TIME'] === $b->getServerParams()['REQUEST_TIME']) {
            return 0;
        }
        if ($a->getServerParams()['REQUEST_TIME'] < $b->getServerParams()['REQUEST_TIME']) {
            return -1;
        }

        return 1;
    }
}