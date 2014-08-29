<?php
require_once 'BaseObject.php';

use CacheBack\Tag;

class TagTest extends BaseObject
{

    public function testGetKeys()
    {
        $t = new Tag($this->predis, 'test');
        $t->setKeyPrefix('cb');

        $this->assertCount(0, $t->getKeys());

        $this->predis->sadd('cb:tag:test', 'cb:foo');

        $this->assertCount(1, $t->getKeys());

        $this->assertEquals(['foo'], $t->getKeys());
    }

    public function testAddKey()
    {
        $t = new Tag($this->predis, 'test');
        $t->setKeyPrefix('cb');
        $t->addKey('test');
        $this->assertTrue($this->predis->exists('cb:tag:test'));
        $this->assertCount(1, $this->predis->smembers('cb:tag:test'));
        $this->assertEquals(['cb:test'], $this->predis->smembers('cb:tag:test'));
    }

    public function testFlush()
    {
        $t = new Tag($this->predis, 'test');
        $t->setKeyPrefix('cb');

        $this->predis->set('cb:foo', 1);
        $t->addKey('foo');
        $t->flush();

        $this->assertFalse($this->predis->exists('cb:foo'));
        $this->assertCount(0, $this->predis->smembers('cb:tag:test'));
    }

}