<?php
namespace Owl\Service;

interface IService {
    public function __construct(array $options = []);

    public function destroy();
}
