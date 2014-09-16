<?php
namespace CacheBack;

trait CacheKeyTrait
{
    /** @var string */
    protected $keyPrefix = 'cb';

    /** @var string */
    protected $key;

    /** @var \Predis\Client  */
    protected $predis;

    public function __construct(\Predis\Client $predis, $key)
    {
        $this->predis = $predis;
        $this->key = $key;
    }


    public function setKeyPrefix($keyPrefix)
    {
        $this->keyPrefix = $keyPrefix;
    }

    public function getRawKeyName()
    {
        return $this->key;
    }

    public function getKeyName($key)
    {
        return "$this->keyPrefix:$key";
    }
}