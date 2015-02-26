<?php
namespace Tests\Controller;

class IndexTest extends \Tests\ControllerTest {
    public function testGet() {
        $response = $this->execute([
            'uri' => '/'
        ]);

        $this->assertEquals(200, $response->getStatus());
        $this->assertRegExp('/<\/html>$/', $response->getBody());
    }

    public function testPost() {
        $response = $this->execute([
            'uri' => '/',
            'method' => 'post',
            'post' => ['foo' => 'bar'],
        ]);

        $this->assertEquals(405, $response->getStatus());
    }
}
