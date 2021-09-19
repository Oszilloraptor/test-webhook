# TestWebhook

A very basic implementation of a temporary webhook for integration testing.

Useful if you need an incoming webhook during testing which is called from other applications.

## Installation 

`composer require --dev rikta/test-webhook`

## Usage

### Initialisation

Simply create an instance of `WebhookServer` and keep the reference alive.
This will provide a webhook-server at a random port.

You can get the url to the webhook of this server-instance with `$server->getUrl()`
You have to provide the external hostname/ip of this machine if your calling application is outside your machine/container

### Querying Data

The `$server->query()` provides a fresh query-object that provides a simple way to query for data.

You can use `$server->query()->get()` to get all currently stored requests as PSR-7 ServerRequestInterface-objects

#### get()

`$query->get()` will 
- get all currently stored requests,
- apply provided sorting
- apply provided filters
- return an array of PSR-7 ServerRequestInterface-objects

example: `$lastTwoRequests = $server->query()->last(2)->get()`

### count()

`$query->count()` will return the amount of matching requests.

### first($n)/last($n)

`$query->first($n)` & `$query->last($n)` will reduce the result to the first/last $n requests.

If no `$n` is provided it will reduce it to a single request.

example: `$lastTwoRequests = $server->query()->last(2)->get()`

### sort(?callable $sortFunction = null)

`$query->sort(?callable $sortFunction = null)` will sort the results with the provided comparator-function (on `ServerRequestInterface`)

If no `$sortFunction` is provided it will sort in chronological order.

example: `$requestChronic = $server->query()->sort()->get()`

### filter(callable $callable)

`$query->filter($callable)` allows to provide a `$callable` to an array_filter around the results.

example: 
```php
$query = $server
      ->query()
      ->filter(fn (ServerRequestInterface $r) => $request->getBody()->getContents() === 'Hello World!');
self::assertEquals(2, $query->count());    
```

### delete()

`$query->delete()` will delete all matching requests.

example: `$server->query()->last(2)->delete()`

### waitForMatchingRequests(int $amount = 1, $maxSeconds = 10)

`$query->waitForMatchingRequests` will wait until $amount matching requests have arrived.
Or throw an LoopTimeoutException after $maxSeconds.

example: `$request = $server->query()->waitForMatchingRequests()->get()[0]`
