<?php
namespace CacheBack;

class Cache
{
    private $predis;
    private $keyPrefix;

    public function __construct(\Predis\Client $predis, $keyPrefix = 'cb')
    {
        $this->predis = $predis;
        $this->keyPrefix = $keyPrefix;
    }

    /**
     * @param $key
     * @param callable $closure
     * @param int $ttl
     * @return Key
     */
    public function __invoke($key, \Closure $closure, $ttl = 86400)
    {
        $keyObj = new Key($this->predis, $key, $closure, $ttl);
        $keyObj->setKeyPrefix($this->keyPrefix);
        return $keyObj;
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
