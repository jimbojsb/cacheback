<?php
namespace CacheBack;

class Tag
{
    use CacheKeyTrait;

    public function addKey($key)
    {
        if (is_string($key)) {
            $key = new Key($this->predis, $key);
            $key->setKeyPrefix($this->keyPrefix);
        }

        $tagKey = $this->getKeyName("tag:$this->key");
        $keyTagIndexKey = $this->getKeyName("tags:" . $key->getRawKeyName());
        $this->predis->sadd($tagKey, $key->getRawKeyName());
        $this->predis->sadd($keyTagIndexKey, $this->key);
    }

    public function removeKey($key)
    {
        if (is_string($key)) {
            $key = new Key($this->predis, $key);
            $key->setKeyPrefix($this->keyPrefix);
        }

        $tagKey = $this->getKeyName("tag:$this->key");
        $keyTagIndexKey = $this->getKeyName("tags:" . $key->getRawKeyName());
        $this->predis->srem($tagKey, $key->getRawKeyName());
        $this->predis->srem($keyTagIndexKey, $this->key);
    }

    /** @return Key[] */
    public function getKeys()
    {
        $keys = [];
        $keysNames = $this->predis->smembers($this->getKeyName("tag:$this->key"));
        foreach ($keysNames as $key) {
            $k = new Key($this->predis, $key);
            $k->setKeyPrefix($this->keyPrefix);
            $keys[] = $k;
        }
        return $keys;
    }

    public function flush()
    {
        $keysToDelete = $this->getKeys();
        foreach ($keysToDelete as $key) {
            $key->flush();
            $this->predis->del($this->getKeyName("tags:" . $key->getRawKeyName()));
        }
        $this->predis->del($this->getKeyName("tag:$this->key"));

    }
}