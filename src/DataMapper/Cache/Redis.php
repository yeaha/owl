<?php

namespace Owl\DataMapper\Cache;

trait Redis
{
    use Hooks;

    protected function getCache($id)
    {
        $key = $this->getCacheKey($id);
        $redis = $this->getCacheService($key);

        try {
            if ($record = $redis->get($key)) {
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
        $redis = $this->getCacheService($key);

        return $redis->delete($key);
    }

    protected function saveCache($id, array $record, $ttl = null)
    {
        $key = $this->getCacheKey($id);
        $redis = $this->getCacheService($key);
        $ttl = $ttl ?: $this->getCacheTTL();

        return $redis->setex($key, $ttl, \Owl\safe_json_encode($record, JSON_UNESCAPED_UNICODE));
    }
}
