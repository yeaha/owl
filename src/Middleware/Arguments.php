<?php
namespace Owl\Middleware;

/**
 * @example
 * $arguments = new Arguments('a', 'b', 'c');
 *
 * echo $arguments[0]."\n";
 * echo $arguments[1]."\n";
 * echo $arguments[2]."\n";
 *
 * var_dump($arguments->toArray());
 */
class Arguments implements \ArrayAccess
{
    private $arguments;

    public function __construct( /*$arguments1[, $arguments2[, ...]]*/)
    {
        $this->arguments = func_get_args();
    }

    public function toArray()
    {
        return $this->arguments;
    }

    public function offsetExists($offset)
    {
        return isset($this->arguments[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->arguments[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->arguments[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->arguments[$offset]);
    }
}
