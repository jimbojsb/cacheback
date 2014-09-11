<?php
namespace CacheBack;

trait CacheKeyTrait
{
    protected $keyPrefix;

    public function setKeyPrefix($keyPrefix)
    {
        $this->keyPrefix = $keyPrefix;
    }

    public function getKeyName($key)
    {
        return "$this->keyPrefix:$key";
    }
}