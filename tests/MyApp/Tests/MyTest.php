<?php

namespace MyApp\Tests;

use Silex\WebTestCase;

class MyTests extends WebTestCase
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../../../app/app.php';
        return $app;
    }

    public function testIndexPage() {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/');

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('ul'));
        $this->assertCount(1, $crawler->filter('h1:contains("News")'));
        $this->assertGreaterThan(0, $crawler->filter('h2')->count());
    }

    public function testCategoryPage() {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/sport');

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('ul'));
        $this->assertCount(1, $crawler->filter('h1:contains("Category")'));
        $this->assertGreaterThan(0, $crawler->filter('h2')->count());
    }

    public function testPostPage() {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/sport/1');

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('ul'));
        $this->assertCount(1, $crawler->filter('h2'));
    }


}

?>