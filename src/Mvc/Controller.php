<?php
namespace Owl\Http;

class Controller {
    public function __beforeExecute($request, $response) {
    }

    public function __afterExecute($request, $response) {
    }

    public function GET($request, $response) {
        throw \Owl\Http\Exception::factory(405);
    }

    public function POST($request, $response) {
        throw \Owl\Http\Exception::factory(405);
    }

    public function PUT($request, $response) {
        throw \Owl\Http\Exception::factory(405);
    }

    public function DELETE($request, $response) {
        throw \Owl\Http\Exception::factory(405);
    }
}
