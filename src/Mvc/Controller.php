<?php
namespace Owl\Mvc;

/**
 * 控制器基类
 */
abstract class Controller {
    /**
     * @param \Owl\Http\Request $request
     * @param \Owl\Http\Response $response
     * @param mixed $...
     */
    public function __beforeExecute(/*\Owl\Http\Request $request, \Owl\Http\Response $response [, mixed $parameter1 [, mixed $...]]*/) {
    }

    /**
     * @param \Owl\Http\Request $request
     * @param \Owl\Http\Response $response
     * @param mixed $...
     */
    public function __afterExecute(/*\Owl\Http\Request $request, \Owl\Http\Response $response [, mixed $parameter1 [, mixed $...]]*/) {
    }

    /**
     * @param \Owl\Http\Request $request
     * @param \Owl\Http\Response $response
     * @param mixed $...
     */
    public function GET(/*\Owl\Http\Request $request, \Owl\Http\Response $response [, mixed $parameter1 [, mixed $...]]*/) {
        throw \Owl\Http\Exception::factory(405);
    }

    /**
     * @param \Owl\Http\Request $request
     * @param \Owl\Http\Response $response
     * @param mixed $...
     */
    public function POST(/*\Owl\Http\Request $request, \Owl\Http\Response $response [, mixed $parameter1 [, mixed $...]]*/) {
        throw \Owl\Http\Exception::factory(405);
    }

    /**
     * @param \Owl\Http\Request $request
     * @param \Owl\Http\Response $response
     * @param mixed $...
     */
    public function PUT(/*\Owl\Http\Request $request, \Owl\Http\Response $response [, mixed $parameter1 [, mixed $...]]*/) {
        throw \Owl\Http\Exception::factory(405);
    }

    /**
     * @param \Owl\Http\Request $request
     * @param \Owl\Http\Response $response
     * @param mixed $...
     */
    public function DELETE(/*\Owl\Http\Request $request, \Owl\Http\Response $response [, mixed $parameter1 [, mixed $...]]*/) {
        throw \Owl\Http\Exception::factory(405);
    }

    /**
     * @param \Owl\Http\Request $request
     * @param \Owl\Http\Response $response
     * @param mixed $...
     */
    public function PATCH(/*\Owl\Http\Request $request, \Owl\Http\Response $response [, mixed $parameter1 [, mixed $...]]*/) {
        throw \Owl\Http\Exception::factory(405);
    }
}
