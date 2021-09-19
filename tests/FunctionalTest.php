<?php declare(strict_types=1);
namespace Rikta\TestWebhook\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Rikta\TestWebhook\WebhookServer;
use const CURLOPT_CUSTOMREQUEST;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_URL;

class FunctionalTest extends TestCase
{
    private const DATA1 = '{"some": "data"}';
    private const DATA2 = '{"other": "data"}';
    private const DATA3 = '{"more": "data"}';
    private const DATA_A = 'An alligator ate all apples.';
    private const DATA_B = 'Bobs Beagles barking becomes bothersome';

    /**
     * @testdox WebhookServer initialises without any errors
     */
    public function testInitialisesWithoutErrors(): WebhookServer
    {
        $this->addToAssertionCount(1);

        return new WebhookServer();
    }

    /**
     * @testdox WebhookServer initialises without any requests stored
     * @depends testInitialisesWithoutErrors
     */
    public function testInitialisesWithNoRequestsStored(WebhookServer $server): WebhookServer
    {
        self::assertEquals(0, $server->query()->count());

        return $server;
    }

    /**
     * @testdox WebhookServer->query()->count() counts one result after the webhook was called once
     * @depends testInitialisesWithNoRequestsStored
     */
    public function testStoresRequestFromWebhook(WebhookServer $server): WebhookServer
    {
        $this->postToServer($server, self::DATA1);
        self::assertEquals(1, $server->query()->count());
        
        return $server;
    }

    /**
     * @testdox WebhookServer->query()->count() counts three results after the webhook was called two times more
     * @depends testStoresRequestFromWebhook
     */
    public function testStoresMultipleSubsequentRequests(WebhookServer $server): WebhookServer
    {
        $this->postToServer($server, self::DATA2);
        $this->postToServer($server, self::DATA3);
        self::assertEquals(3, $server->query()->count());
        
        return $server;
    }

    /**
     * @testdox WebhookServer->query()->sort() sorts requests chronologically
     * @depends testStoresMultipleSubsequentRequests
     */
    public function testSortFunctionSortsByTime(WebhookServer $server): WebhookServer
    {
        $sorted = $server->query()->sort()->get();
        self::assertEquals(self::DATA1, $sorted[0]->getBody());
        self::assertEquals(self::DATA2, $sorted[1]->getBody());
        self::assertEquals(self::DATA3, $sorted[2]->getBody());
        
        return $server;
    }

    /**
     * @testdox WebhookServer->query()->first() returns first request
     * @depends testSortFunctionSortsByTime
     */
    public function testFirstReturnsFirstRequest(WebhookServer $server): WebhookServer
    {
        self::assertEquals(self::DATA1, $server->query()->first()->get()[0]->getBody());
        
        return $server;
    }

    /**
     * @testdox WebhookServer->query()->last() returns last request
     * @depends testFirstReturnsFirstRequest
     */
    public function testLastReturnsLastRequest(WebhookServer $server): WebhookServer
    {
        self::assertEquals(self::DATA3, $server->query()->last()->get()[0]->getBody());

        return $server;
    }

    /**
     * @testdox WebhookServer->query()->filter() filters elements
     * @depends testFirstReturnsFirstRequest
     */
    public function testFilterFiltersElements(WebhookServer $server): WebhookServer
    {
        $query = $server->query()->filter(fn (ServerRequestInterface $request) => str_contains($request->getBody()->getContents(), 'm'));
        self::assertEquals(2, $query->count());

        return $server;
    }

    /**
     * @testdox WebhookServer->query()->first()->delete() deletes a request
     * @depends testLastReturnsLastRequest
     */
    public function testDeleteDeletesSelectedFiles(WebhookServer $server): WebhookServer
    {
        $server->query()->first()->delete();
        self::assertEquals(2, $server->query()->count());
        
        return $server;
    }

    /**
     * @testdox WebhookServer->query()->delete() deletes all requests
     * @depends testDeleteDeletesSelectedFiles
     */
    public function testDeleteOnFreshQueryDeletesEverything(WebhookServer $server): WebhookServer
    {
        $server->query()->delete();
        self::assertEquals(0, $server->query()->count());
        
        return $server;
    }

    /**
     * @testdox Multiple instances of WebhookServer can be instantiated at the same time
     * @return WebhookServer[]
     */
    public function testTwoInstancesCanExistSimultaneously(): array
    {
        $serverA = new WebhookServer();
        $serverB = new WebhookServer();
        $this->addToAssertionCount(1);
        return [$serverA, $serverB];
    }

    /**
     * @testdox Multiple simultaneous instances of WebhookServer use different ports
     * @depends testTwoInstancesCanExistSimultaneously
     * @param WebhookServer[] $servers
     * @return WebhookServer[]
     */
    public function testTwoInstancesUseDifferentPorts(array $servers): array
    {
        [$serverA, $serverB] = $servers;
        self::assertNotEquals($serverA->getPort(), $serverB->getPort());
        return [$serverA, $serverB];
    }

    /**
     * @testdox Multiple simultaneous instances of WebhookServer don't interfere with each other
     * @depends testTwoInstancesCanExistSimultaneously
     * @param WebhookServer[] $servers
     * @return WebhookServer[]
     */
    public function testTwoInstancesWebhooksDontInterfere(array $servers): array
    {
        [$serverA, $serverB] = $servers;
        $this->postToServer($serverA, self::DATA_A);
        $this->postToServer($serverB, self::DATA_B);

        self::assertEquals(1, $serverA->query()->count());
        self::assertEquals(1, $serverB->query()->count());

        self::assertEquals(self::DATA_A, $serverA->query()->get()[0]->getBody());
        self::assertEquals(self::DATA_B, $serverB->query()->get()[0]->getBody());

        return [$serverA, $serverB];
    }

    private function postToServer(WebhookServer $server, string $content): void
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $server->getUrl(),
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $content,
        ));

        curl_exec($curl);
        curl_close($curl);
    }
}