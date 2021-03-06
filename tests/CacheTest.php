<?php
require_once 'BaseObject.php';

use CacheBack\Cache;

class CacheBackTest extends BaseObject
{
    public function testInvoke()
    {
        $c = new Cache($this->predis);
        $key = $c('test', $this->closure);

        $this->assertInstanceOf('\CacheBack\Key', $key);

        $ttlProp = new ReflectionProperty('\CacheBack\Key', 'ttl');
        $ttlProp->setAccessible(true);
        $this->assertEquals(86400, $ttlProp->getValue($key));

        $keyProp = new ReflectionProperty('\CacheBack\Key', 'key');
        $keyProp->setAccessible(true);
        $this->assertEquals('test', $keyProp->getValue($key));

        $closureProp = new ReflectionProperty('\CacheBack\Key', 'closure');
        $closureProp->setAccessible(true);
        $this->assertInstanceOf('\Closure', $closureProp->getValue($key));
        $this->assertEquals(spl_object_hash($this->closure), spl_object_hash($closureProp->getValue($key)));


        $predisProp = new ReflectionProperty('\CacheBack\Key', 'predis');
        $predisProp->setAccessible(true);
        $this->assertInstanceOf('\Predis\Client', $predisProp->getValue($key));
        $this->assertEquals(spl_object_hash($this->predis), spl_object_hash($predisProp->getValue($key)));

        $keyPrefixProp = new ReflectionProperty('\CacheBack\Key', 'keyPrefix');
        $keyPrefixProp->setAccessible(true);
        $this->assertEquals('cb', $keyPrefixProp->getValue($key));
    }

    public function testFlush()
    {
        $c = new Cache($this->predis);
        $k = $c('test', $this->closure);
        $k->tag('foo');
        $k->get();

        $this->assertCount(3, $this->predis->keys('cb:*'));
        $c->flush();
        $this->assertCount(0, $this->predis->keys('cb:*'));
    }

    public function testFlushKey()
    {
        $c = new Cache($this->predis);
        $k = $c('test', $this->closure);
        $k->get();
        $this->assertCount(1, $this->predis->keys('cb:*'));
        $c->flushKey('test');
        $this->assertCount(0, $this->predis->keys('cb:*'));
    }

    public function testHitMissClosures()
    {
        $c = new Cache($this->predis);
        $c->setOnMiss(function() {});
        $c->setOnHit(function() {});
        $k = $c('test', $this->closure);

        $rp = new ReflectionProperty('\CacheBack\Key', 'onMiss');
        $rp->setAccessible(true);
        $value = $rp->getValue($k);
        $this->assertInstanceOf('\Closure', $value);

        $rp = new ReflectionProperty('\CacheBack\Key', 'onHit');
        $rp->setAccessible(true);
        $value = $rp->getValue($k);
        $this->assertInstanceOf('\Closure', $value);

    }

    public function testDisable()
    {
        $c = new Cache($this->predis);
        $c->disable();
        $this->predis->set('cb:test', serialize(2));
        $k = $c('test', $this->closure);
        $val = $k->get();
        $this->assertEquals(1, $val);

    }

    public function testFlushTag()
    {
        $this->predis->set("cb:test", 1);
        $this->predis->sadd("cb:tag:foo", "test");
        $this->predis->sadd("cb:tags:test", "foo");

        $this->assertCount(3, $this->predis->keys("cb:*"));

        $c = new Cache($this->predis);
        $c->flushTag("foo");
        $this->assertCount(0, $this->predis->keys("cb:*"));
    }

}