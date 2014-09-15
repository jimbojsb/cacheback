<?php
namespace CacheBack;

class Key
{
    /** @var int */
    protected $ttl;

    /** @var \Closure  */
    protected $closure;

    /** @var \Predis\Client  */
    protected $predis;

    protected $key;

    protected $enabled;

    use CacheKeyTrait;

    public function __construct(\Predis\Client $predis, $key, \Closure $closure = null, $ttl = 86400, $enabled = true)
    {
        $this->ttl = $ttl;
        $this->closure = $closure;
        $this->predis = $predis;
        $this->key = $key;
        $this->enabled = $enabled;
    }

    public function tag($tag)
    {
        $tag = new Tag($this->predis, $tag);
        $tag->setKeyPrefix($this->keyPrefix);
        $tag->addKey($this->key);
    }

    public function get()
    {
        if (!($this->closure instanceof \Closure)) {
            throw new \RuntimeException("Cannot get a key with an undefined closure");
        }

        if ($this->enabled) {
            $data = $this->predis->get($this->getKeyName($this->key));
        }
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