<?php
namespace CacheBack;

class Key
{
    /** @var int */
    protected $ttl = 86400;

    /** @var \Closure  */
    protected $closure;

    /** @var boolean */
    protected $enabled = true;

    protected $onHit;

    protected $onMiss;

    use CacheKeyTrait;

    public function setEnabled($enabled)
    {
        if (is_bool($enabled)) {
            $this->enabled = $enabled;
        } else {
            throw new \InvalidArgumentException("\$enabled must be boolean");
        }
    }

    public function setClosure(\Closure $closure)
    {
        $this->closure = $closure;
    }

    public function setTtl($ttl)
    {
        if (is_int($ttl)) {
            $this->ttl = $ttl;
        } else {
            throw new \InvalidArgumentException("TTL must be an integer");
        }
    }

    public function tag($tag)
    {
        $tag = new Tag($this->predis, $tag);
        $tag->setKeyPrefix($this->keyPrefix);
        $tag->addKey($this);
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
            if ($this->enabled) {
                $this->predis->setex($this->getKeyName($this->key), $this->ttl, serialize($data));
            }
            if ($this->onMiss instanceof \Closure) {
                call_user_func_array($this->onMiss, [$this, $data]);
            }
            return $data;
        } else {
            $value = unserialize($data);
            if ($this->onHit instanceof \Closure) {
                call_user_func_array($this->onHit, [$this, $value]);
            }
            return $value;
        }
    }

    /**
     * @return Tag[]
     */
    public function getTags()
    {
        $tags = [];
        $tagsKeys = $this->predis->smembers($this->getKeyName("tags:$this->key"));
        foreach ($tagsKeys as $tagKey) {
            $tag = new Tag($this->predis, $tagKey);
            $tag->setKeyPrefix($this->keyPrefix);
            $tags[] = $tag;
        }
        return $tags;
    }

    public function flush()
    {
        $this->predis->del($this->getKeyName($this->key));
    }

    public function setOnHit($onHit)
    {
        $this->onHit = $onHit;
    }

    public function setOnMiss($onMiss)
    {
        $this->onMiss = $onMiss;
    }


}