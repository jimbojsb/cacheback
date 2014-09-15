<?php
require_once 'BaseObject.php';

use CacheBack\Key;
use CacheBack\Tag;

class KeyTest extends BaseObject
{
    public function testGet()
    {
        $k = new Key($this->predis, 'test', $this->closure);
        $k->setKeyPrefix('cb');
        $value = $k->get();
        $this->assertTrue($this->predis->exists('cb:test'));
        $this->assertEquals(1, $value);
        $this->assertEquals(serialize(1), $this->predis->get('cb:test'));
    }


    public function testTtl()
    {
        $k = new Key($this->predis, 'test', $this->closure, 300);
        $k->setKeyPrefix('cb');
        $k->get();
        $this->assertTrue($this->predis->exists('cb:test'));
        $this->assertEquals(300, $this->predis->ttl('cb:test'));
    }

    public function testTag()
    {
        $k = new Key($this->predis, 'test', function() {
            $this->tag('foo');
            return 1;
        });
        $k->setKeyPrefix('cb');
        $k->get();

        $this->assertTrue($this->predis->exists('cb:test'));
        $t = new Tag($this->predis, 'foo');
        $t->setKeyPrefix('cb');
        $this->assertEquals(['test'], $t->getKeys());
    }

    public function testFlush()
    {
        $k = new Key($this->predis, 'test', $this->closure);
        $k->setKeyPrefix('cb');
        $k->get();
        $this->assertTrue($this->predis->exists('cb:test'));
        $k->flush();
        $this->assertFalse($this->predis->exists('cb:test'));
    }

    public function testGetWithUndefinedClosure()
    {
        $k = new Key($this->predis, 'test');
        try {
            $k->get();
            $this->fail("Should have thrown a runtime exception on undefined key closure");
        } catch (Exception $e) {
        }
    }

}