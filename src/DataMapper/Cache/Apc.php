<?php

namespace Owl\DataMapper\Cache;

trait Apc
{
    use Hooks;

    protected function getCache($id)
    {
        $key = $this->getCacheKey($id);
        $fn = $this->getFn('fetch');

        return $fn($key) ?: [];
    }

    protected function deleteCache($id)
    {
        $key = $this->getCacheKey($id);
        $fn = $this->getFn('delete');

        return $fn($key);
    }

    protected function saveCache($id, array $record, $ttl = null)
    {
        $key = $this->getCacheKey($id);
        $ttl = $ttl ?: $this->getCacheTTL();
        $fn = $this->getFn('store');

        return $fn($key, $record, $ttl);
    }

    private function getFn($method)
    {
        static $prefix;

        if (!$prefix) {
            if (extension('apcu')) {
                $prefix = 'apcu_';
            } elseif (extension('apc')) {
                $prefix = 'apc_';
            } else {
                throw new \Exception('Require APC or APCu extension!');
            }
        }

        return $prefix.$method;
    }
}
