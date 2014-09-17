<?php
require_once 'BaseObject.php';

use CacheBack\Key;
use CacheBack\Tag;

class KeyTest extends BaseObject
{
    public function testGet()
    {
        $k = new Key($this->predis, 'test');
        $k->setClosure($this->closure);
        $value = $k->get();
        $this->assertTrue($this->predis->exists('cb:test'));
        $this->assertEquals(1, $value);
        $this->assertEquals(serialize(1), $this->predis->get('cb:test'));
        $cachedValue = $k->get();
        $this->assertEquals(1, $cachedValue);
    }


    public function testTtl()
    {
        $k = new Key($this->predis, 'test');
        $k->setClosure($this->closure);
        $k->setTtl(300);
        $k->get();
        $this->assertTrue($this->predis->exists('cb:test'));
        $this->assertEquals(300, $this->predis->ttl('cb:test'));
    }

    public function testTag()
    {
        $k = new Key($this->predis, 'test');
        $k->setClosure(function() {
            $this->tag('foo');
            return 1;
        });
        $k->get();

        $this->assertTrue($this->predis->exists('cb:test'));
        $t = new Tag($this->predis, 'foo');
        $t->setKeyPrefix('cb');
        $keys = $t->getKeys();
        $key = $keys[0];
        $this->assertEquals('test', $key->getRawKeyName());
    }

    public function testFlush()
    {
        $k = new Key($this->predis, 'test');
        $k->setClosure($this->closure);
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

    public function testSetTtl()
    {
        $k = new Key($this->predis, 'test');
        try {
            $k->setTtl('sdf');
            $this->fail("Setting a non-integer ttl should throw invalid argument exception");
        } catch (Exception $e) {
        }

        $k->setTtl(300);
        $rp = new ReflectionProperty("\CacheBack\Key", "ttl");
        $rp->setAccessible(true);
        $this->AssertEquals(300, $rp->getValue($k));
    }

    public function testSetEnabled()
    {
        $k = new Key($this->predis, 'test');
        try {
            $k->setEnabled('sdf');
            $this->fail("Setting a non-boolean enabled should throw invalid argument exception");
        } catch (Exception $e) {
        }

        $k->setEnabled(false);
        $rp = new ReflectionProperty("\CacheBack\Key", "enabled");
        $rp->setAccessible(true);
        $this->AssertEquals(false, $rp->getValue($k));
    }

    public function testGetTags()
    {
        $k = new Key($this->predis, 'test');
        $k->tag('testTag');
        $tags = $k->getTags();
        $this->assertCount(1, $tags);
        $tag = $tags[0];
        $this->assertInstanceOf("\CacheBack\Tag", $tag);
        $this->assertEquals('testTag', $tag->getRawKeyName());
    }

    public function testHitMissClosure()
    {
        $k = new Key($this->predis, 'hit');
        $k->setClosure($this->closure);

        $shouldFail = true;
        $k->setOnHit(function() use(&$shouldFail) {
            $shouldFail = false;
        });
        $k->get(); //miss
        $k->get(); //hit
        $this->assertFalse($shouldFail);

        $k = new Key($this->predis, 'miss');
        $k->setClosure($this->closure);

        $shouldFail = true;
        $k->setOnMiss(function() use(&$shouldFail) {
            $shouldFail = false;
        });
        $k->get(); //miss
        $this->assertFalse($shouldFail);
    }
}