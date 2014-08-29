<?php
class BaseObject extends PHPUnit_Framework_TestCase
{
    /** @var \Predis\Client */
    protected $predis;

    /** @var \Closure */
    protected $closure;

    public function setUp()
    {
        $p = new Predis\Client;
        $this->predis = $p;

        $closure = function() {
            return 1;
        };
        $this->closure = $closure;
    }

    public function tearDown()
    {
        $keys = $this->predis->keys("cb:*");
        foreach ($keys as $key) {
            $this->predis->del($key);
        }
        unset($this->predis);
    }
}