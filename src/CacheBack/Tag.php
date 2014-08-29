<?php
namespace CacheBack;

class Tag
{
    private $tag;

    /** @var \Predis\Client */
    private $predis;

    use CacheKeyTrait;

    public function __construct(\Predis\Client $predis, $tag)
    {
        $this->tag = $tag;
        $this->predis = $predis;
    }

    public function addKey($key)
    {
        $tagKey = $this->getKeyName("tag:$this->tag");
        $this->predis->sadd($tagKey, $this->getKeyName($key));
    }

    public function removeKey($key)
    {
        $tagKey = $this->getKeyName("tag:$this->tag");
        $this->predis->srem($tagKey, $this->getKeyName($key));
    }

    public function getKeys()
    {
        $keys = $this->predis->smembers($this->getKeyName("tag:$this->tag"));
        array_walk($keys, function (&$el) {
            $el = str_replace("$this->keyPrefix:", '', $el);
        });
        return $keys;
    }

    public function flush()
    {
        $keysToDelete = $this->getKeys();
        $this->predis->pipeline(function($r) use ($keysToDelete) {
            foreach ($keysToDelete as $key) {
                $r->del($this->getKeyName($key));
                $this->predis->srem($this->getKeyName("tag:$this->tag"), $this->getKeyName($key));
            }
        });
    }
}