<?php
namespace CacheBack;

class Key
{
    /** @var int */
    private $ttl;

    /** @var \Closure  */
    private $closure;

    /** @var \Predis\Client  */
    private $predis;

    private $key;

    use CacheKeyTrait;

    public function __construct(\Predis\Client $predis, $key, \Closure $closure, $ttl = 86400)
    {
        $this->ttl = $ttl;
        $this->closure = $closure;
        $this->predis = $predis;
        $this->key = $key;
    }

    public function tag($tag)
    {
        $tag = new Tag($this->predis, $tag);
        $tag->setKeyPrefix($this->keyPrefix);
        $tag->addKey($this->key);
    }

    public function get()
    {
        $data = $this->predis->get($this->getKeyName($this->key));
        if ($data === null) {
            $boundCallback = $this->closure->bindTo($this);
            $data = $boundCallback();
            $this->predis->setex($this->getKeyName($this->key), $this->ttl, serialize($data));
            return $data;
        } else {
            return unserialize($data);
        }
    }

    public function flush()
    {
        $this->predis->del($this->getKeyName($this->key));
    }
}