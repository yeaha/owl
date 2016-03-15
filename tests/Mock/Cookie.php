<?php

namespace Tests\Mock;

class Cookie
{
    use \Owl\Traits\Singleton;

    protected $data = [];

    public function set($name, $value, $expire = 0, $path = '/', $domain = null)
    {
        $domain = $this->normalizeDomain($domain);
        $path = $this->normalizePath($path);
        $this->data[$domain][$path][$name] = [$value, (int) $expire];
    }

    public function get($path = '/', $domain = null)
    {
        $domain = $this->normalizeDomain($domain);
        $path = $this->normalizePath($path);
        $now = time();

        $cookies = [];
        foreach ($this->data as $cookie_domain => $path_list) {
            if (substr($cookie_domain, strlen($cookie_domain) - strlen($domain)) != $domain) {
                continue;
            }

            foreach ($path_list as $cookie_path => $cookie_list) {
                if (strpos($path, $cookie_path) !== 0) {
                    continue;
                }

                foreach ($cookie_list as $name => $cookie) {
                    list($value, $expire) = $cookie;
                    if ($expire && $expire < $now) {
                        continue;
                    }

                    $cookies[$name] = $value;
                }
            }
        }

        return $cookies;
    }

    public function reset()
    {
        $this->data = [];
    }

    public function apply(\Owl\Http\Response $response)
    {
        foreach ($response->getCookies() as $cookie) {
            call_user_func_array([$this, 'set'], $cookie);
        }
    }

    private function normalizePath($path)
    {
        $path = trim(strtolower($path), '/');

        return $path ? '/'.$path.'/' : '/';
    }

    private function normalizeDomain($domain)
    {
        $domain = trim(strtolower($domain), '.');

        return $domain ? '.'.$domain.'.' : '.';
    }
}
