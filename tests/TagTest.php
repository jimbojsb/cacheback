<?php
require_once 'BaseObject.php';

use CacheBack\Tag;

class TagTest extends BaseObject
{

    public function testGetKeys()
    {
        $t = new Tag($this->predis, 'test');
        $this->assertCount(0, $t->getKeys());
        $this->predis->sadd('cb:tag:test', 'foo');
        $this->assertCount(1, $t->getKeys());
        $keys = $t->getKeys();
        $key = $keys[0];
        $this->assertEquals('foo', $key->getRawKeyName());
    }

    public function testAddKey()
    {
        $t = new Tag($this->predis, 'test');
        $t->addKey('foo');
        $this->assertTrue($this->predis->exists('cb:tag:test'));
        $this->assertTrue($this->predis->exists('cb:tags:foo'));
        $this->assertCount(1, $this->predis->smembers('cb:tag:test'));
        $this->assertCount(1, $this->predis->smembers('cb:tags:foo'));
        $this->assertEquals(['foo'], $this->predis->smembers('cb:tag:test'));
        $this->assertEquals(['test'], $this->predis->smembers('cb:tags:foo'));
    }

    public function testRemoveKey()
    {
        $t = new Tag($this->predis, 'test');
        $this->predis->sadd('cb:tag:test', 'foo');
        $this->predis->sadd('cb:tags:foo', 'test');
        $this->assertCount(1, $this->predis->smembers('cb:tag:test'));
        $this->assertCount(1, $this->predis->smembers('cb:tags:foo'));
        $t->removeKey('foo');
        $this->assertEquals([], $this->predis->smembers('cb:tag:test'));
        $this->assertEquals([], $this->predis->smembers('cb:tags:foo'));
    }

    public function testFlush()
    {
        $t = new Tag($this->predis, 'test');
        $t->setKeyPrefix('cb');

        $this->predis->set('cb:foo', 1);
        $t->addKey('foo');
        $t->flush();

        $this->assertFalse($this->predis->exists('cb:foo'));
        $this->assertFalse($this->predis->exists('cb:tag:test'));
        $this->assertFalse($this->predis->Exists('cb:tags:foo'));
    }

}