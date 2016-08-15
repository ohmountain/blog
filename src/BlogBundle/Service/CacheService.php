<?php
/**
 * Created by PhpStorm.
 * User: renshan
 * Date: 16-8-3
 * Time: 下午10:48
 */

namespace BlogBundle\Service;


use Doctrine\Common\Cache\RedisCache;

class CacheService
{
    private $cacheDriver;

    public function __construct()
    {
        $this->cacheDriver = new RedisCache();

        $redis = new \Redis();
        $redis->connect('localhost');

        $this->cacheDriver->setNamespace('blog_cache');
        $this->cacheDriver->setRedis($redis);

    }

    public function get($key)
    {
        return $this->cacheDriver->fetch($key);
    }

    public function set($key, $data, $expire = 600)
    {
        return $this->cacheDriver->save($key, $data, $expire);
    }

    public final function clear($key)
    {
        $this->cacheDriver->delete($key);
    }

    public final function clearAll()
    {
        $this->cacheDriver->deleteAll();
    }
}