<?php
use CacheBack\Cache;

class CacheBackTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \CacheBack\Cache
     */
    private $cache;

    /**
     * @var \Predis\Client
     */
    private $predis;

    public function setUp()
    {
        $p = new Predis\Client;
        $this->predis = $p;

        $c = new Cache($p);
        $this->cache = $c;
    }

    public function tearDown()
    {
        $keys = $this->predis->keys("cb:*");
        foreach ($keys as $key) {
            $this->predis->del($key);
        }
        unset($this->cache);
        unset($this->predis);
    }

    public function testPlainCache()
    {
        $c = $this->cache;

        $this->assertFalse($this->predis->exists('cb:test'));

        $data = $c->get('test', function() {
            return 'bar';
        });

        $this->assertTrue($this->predis->exists('cb:test'));

        $this->assertEquals('bar', $data);
        $this->assertEquals('bar', $c->get('test'));
    }

    public function testTtl()
    {
        $c = $this->cache;
        $c->get('test', function() {
            return 'bar';
        }, 2);
        $this->assertTrue($this->predis->exists('cb:test'));
        sleep(3);
        $this->assertFalse($this->predis->exists('cb:test'));
    }

    public function testGetKeysForTag()
    {
        $c = $this->cache;
        $c->get('test', function() {
            $this->tag('foo');
            return 'bar';
        });
        $this->assertEquals(['cb:test'], $this->predis->smembers('cb:tag:foo'));
        $this->assertEquals(['cb:test'], $c->getKeysForTag('foo'));
    }

    public function testTag()
    {
        $c = $this->cache;
        $c->get('test', function() {
            $this->tag('foo');
            return 'bar';
        });
        $this->assertTrue($this->predis->exists('cb:tag:foo'));
        $this->assertEquals(['cb:test'], $this->predis->smembers('cb:tag:foo'));
        $c->flushTag('foo');
        $this->assertFalse($this->predis->exists('cb:tag:foo'));
    }

    public function testFlush()
    {
        $c = $this->cache;
        $c->get('test', function() {
            $this->tag('foo');
            return 'bar';
        });
        $c->get('test1', function() {
            return 'baz';
        });

        $this->assertCount(3, $this->predis->keys('cb:*'));
        $c->flush();
        $this->assertCount(0, $this->predis->keys('cb:*'));
    }

}