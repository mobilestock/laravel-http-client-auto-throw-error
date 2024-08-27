<?php

namespace MobileStock\service\Cache;

use Closure;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @deprecated
 */
class CacheManager
{
    public static function redis(): AbstractAdapter
    {
        $password = env('REDIS_PASSWORD');
        $host = env('REDIS_HOST');
        $port = env('REDIS_PORT');
        $redis = RedisAdapter::createConnection("redis://{$password}@{$host}:{$port}");
        if ($redis->ping()) {
            return new RedisAdapter($redis, '', 0, new JsonMarshaller());
        }
        throw new \Exception('Redis não está disponível');
    }
    /**
     * @see https://github.com/mobilestock/backend/issues/137
     */
    public static function sobrescreveMergeByLifetime(CacheInterface $cache): void
    {
        Closure::bind(
            function () {
                self::$mergeByLifetime = Closure::bind(
                    static function ($deferred, $namespace, &$expiredIds, $getId, $defaultLifetime) {
                        $byLifetime = [];
                        $now = microtime(true);
                        $expiredIds = [];

                        foreach ($deferred as $key => $item) {
                            $key = (string) $key;
                            if (null === $item->expiry) {
                                $ttl = 0 < $defaultLifetime ? $defaultLifetime : 0;
                            } elseif (!$item->expiry) {
                                $ttl = 0;
                            } elseif (0 >= ($ttl = (int) (0.1 + $item->expiry - $now))) {
                                $expiredIds[] = $getId($key);
                                continue;
                            }
                            if (isset(($metadata = $item->newMetadata)[CacheItem::METADATA_TAGS])) {
                                unset($metadata[CacheItem::METADATA_TAGS]);
                            }
                            // For compactness, expiry and creation duration are packed in the key of an array, using magic numbers as separators
                            $byLifetime[$ttl][$getId($key)] = $item->value;
                        }

                        return $byLifetime;
                    },
                    null,
                    CacheItem::class
                );
            },
            $cache,
            AbstractAdapter::class
        )();
    }
}
