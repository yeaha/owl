<?php

namespace Owl\DataMapper\Cache;

trait Memcached
{
    use Hooks;

    protected function getCache($id)
    {
        $key = $this->getCacheKey($id);
        $memcached = $this->getCacheService($key);

        try {
            if ($record = $memcached->get($key)) {
                $record = \Owl\safe_json_decode($record, true);
            }

            return $record ?: [];
        } catch (\UnexpectedValueException $exception) {
            if (DEBUG) {
                throw $exception;
            }

            return [];
        }
    }

    protected function deleteCache($id)
    {
        $key = $this->getCacheKey($id);
        $memcached = $this->getCacheService($key);

        return $memcached->delete($key);
    }

    protected function saveCache($id, array $record, $ttl = null)
    {
        $key = $this->getCacheKey($id);
        $memcached = $this->getCacheService($key);
        $ttl = $ttl ?: $this->getCacheTTL();

        return $memcached->set($key, \Owl\safe_json_encode($record, JSON_UNESCAPED_UNICODE), $ttl);
    }
}
