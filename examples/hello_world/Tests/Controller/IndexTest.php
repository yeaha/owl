<?php
namespace Tests\Controller;

class IndexTest extends \Tests\ControllerTest {
    public function testGet() {
        $response = $this->execute([
            'uri' => '/'
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertRegExp('/<\/html>$/', $response->getBody());
        $this->assertEquals('bar', $response->getCookie('foo'));
        $this->assertFalse($response->getCookie('bar'));
    }

    public function testPost() {
        $response = $this->execute([
            'uri' => '/',
            'method' => 'post',
            'post' => ['foo' => 'bar'],
        ]);

        $this->assertEquals(405, $response->getStatusCode());
    }
}
