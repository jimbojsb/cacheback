<?php
namespace CacheBack;

class Cache
{
    protected $predis;
    protected $keyPrefix;
    protected $enabled = true;

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

    /**
     * @param $key
     * @param callable $closure
     * @param int $ttl
     * @return Key
     */
    public function __invoke($key, \Closure $closure, $ttl = 86400)
    {
        $keyObj = new Key($this->predis, $key, $closure, $ttl, $this->enabled);
        $keyObj->setKeyPrefix($this->keyPrefix);
        return $keyObj;
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
