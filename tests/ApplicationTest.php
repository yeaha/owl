<?php
namespace tests;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Owl\Http\Exception
     * @expectedExceptionCode 501
     */
    public function test501()
    {
        $app = new \Owl\Application();
        $app->setExceptionHandler(function ($exception) {
            throw $exception;
        });

        $request = \Owl\Http\Request::factory([
            'method' => 'FOO'
        ]);
        $response = new \Owl\Http\Response();

        $app->execute($request, $response);
    }
}
