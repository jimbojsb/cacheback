<?php
namespace CacheBack;

class Cache
{
    private $predis;
    private $keyPrefix;
    private $activeKey = '';

    public function __construct(\Predis\Client $predis, $keyPrefix = 'cb')
    {
        $this->predis = $predis;
        $this->keyPrefix = $keyPrefix;
    }

    public function get($cacheKey, callable $callback = null, $ttl = 86400)
    {
        $data = $this->predis->get($this->getKeyName($cacheKey));
        if ($data === null) {
            $this->activeKey = $cacheKey;
            $boundCallback = $callback->bindTo($this);
            $data = $boundCallback();
            $this->predis->setex($this->getKeyName($cacheKey), $ttl, $data);
            $this->activeKey = '';
        }
        return $data;
    }

    public function clear($cacheKey)
    {
        $this->predis->del($this->getKeyName($cacheKey));
    }

    public function getKeyName($cacheKey)
    {
        return "$this->keyPrefix:$cacheKey";
    }

    public function tag($tag)
    {
        $tagKey = $this->getKeyName("tag:$tag");
        $this->predis->sadd($tagKey, $this->getKeyName($this->activeKey));
    }

    public function getKeysForTag($tag)
    {
        return $this->predis->smembers($this->getKeyName("tag:$tag"));
    }

    public function flushTag($tag)
    {
        $keysToDelete = $this->getKeysForTag($tag);
        $keysToDelete[] = $this->getKeyName("tag:$tag");
        $this->predis->pipeline(function($r) use ($keysToDelete) {
            foreach ($keysToDelete as $key) {
                $r->del($key);
            }
        });
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
