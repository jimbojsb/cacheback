<?php
namespace CacheBack;

class Cache
{
    protected $predis;
    protected $keyPrefix;
    protected $enabled = true;
    protected $onMiss;
    protected $onHit;

    public function __construct(\Predis\Client $predis, $keyPrefix = 'cb')
    {
        $this->predis = $predis;
        $this->keyPrefix = $keyPrefix;
    }

    public function disable()
    {
        $this->enabled = false;
    }

    public function enable()
    {
        $this->enabled = true;
    }

    public function setOnMiss(\Closure $onMiss)
    {
        $this->onMiss = $onMiss;
    }

    public function setOnHit(\Closure $onHit)
    {
        $this->onHit = $onHit;
    }


    /**
     * @param $key
     * @param callable $closure
     * @param int $ttl
     * @return Key
     */
    public function __invoke($key, \Closure $closure, $ttl = 86400)
    {
        $keyObj = new Key($this->predis, $key);
        $keyObj->setKeyPrefix($this->keyPrefix);
        $keyObj->setTtl($ttl);
        $keyObj->setClosure($closure);
        $keyObj->setOnHit($this->onHit);
        $keyObj->setOnMiss($this->onMiss);
        $keyObj->setEnabled($this->enabled);
        return $keyObj;
    }

    public function flushTag($tag)
    {
        $tag = new Tag($this->predis, $tag);
        $tag->setKeyPrefix($this->keyPrefix);
        $tag->flush();
    }

    public function flushKey($key)
    {
        $key = new Key($this->predis, $key);
        $key->setKeyPrefix($this->keyPrefix);
        $key->flush();
    }

    public function flush()
    {
        $keysToDelete = $this->predis->keys("$this->keyPrefix:*");
        $this->predis->pipeline(function($r) use ($keysToDelete) {
            foreach ($keysToDelete as $key) {
                $r->del($key);
            }
        });
    }
}
