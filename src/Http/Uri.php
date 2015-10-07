<?php
namespace Owl\Http;

use Psr\Http\Message\UriInterface;

class Uri implements UriInterface {
    static public $standard_port = [
        'ftp' => 21,
        'ssh' => 22,
        'smtp' => 25,
        'http' => 80,
        'pop3' => 110,
        'https' => 443,
    ];

    protected $scheme;
    protected $host;
    protected $port;
    protected $user;
    protected $password;
    protected $path;
    protected $query;
    protected $fragment;

    public function __construct($uri = '') {
        $parsed = [];
        if ($uri) {
            $parsed = parse_url($uri) ?: [];
        }

        $this->scheme = isset($parsed['scheme']) ? $parsed['scheme'] : '';
        $this->host = isset($parsed['host']) ? $parsed['host'] : '';
        $this->port = isset($parsed['port']) ? $parsed['port'] : null;
        $this->user = isset($parsed['user']) ? $parsed['user'] : '';
        $this->password = isset($parsed['pass']) ? $parsed['pass'] : '';
        $this->path = isset($parsed['path']) ? $parsed['path'] : '/';
        $this->query = isset($parsed['query']) ? $parsed['query'] : '';
        $this->fragment = isset($parsed['fragment']) ? $parsed['fragment'] : '';
    }

    public function getScheme() {
        return $this->scheme;
    }

    public function getAuthority() {
        $authority = $this->getHost();

        if ($user_info = $this->getUserInfo()) {
            $authority = $user_info.'@'.$authority;
        }

        if ($port = $this->getPort()) {
            $authority = $authority.':'.$port;
        }

        return $authority;
    }

    public function getUserInfo() {
        $user_info = $this->user;

        if ($user_info !== '' && $this->password) {
            $user_info .= ':'.$this->password;
        }

        return $user_info;
    }

    public function getHost() {
        return $this->host;
    }

    public function getPort() {
        $port = $this->port;

        if ($port === null) {
            return null;
        }

        $scheme = $this->getScheme();
        if (!$scheme || !isset(self::$standard_port[$scheme])) {
            return $port;
        }

        return $port === self::$standard_port[$scheme]
             ? null
             : $port;
    }

    public function getPath() {
        return $this->path;
    }

    public function getExtension() {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    public function getQuery() {
        return $this->query;
    }

    public function getFragment() {
        return $this->fragment;
    }

    public function withScheme($scheme) {
        $uri = clone $this;
        $uri->scheme = $scheme;

        return $uri;
    }

    public function withUserInfo($user, $password = null) {
        $uri = clone $this;
        $uri->user = $user;
        $uri->password = $password;

        return $uri;
    }

    public function withHost($host) {
        $uri = clone $this;
        $uri->host = $host;

        return $uri;
    }

    public function withPort($port) {
        $uri = clone $this;
        $uri->port = (int)$port;

        return $uri;
    }

    public function withPath($path) {
        $uri = clone $this;
        $uri->path = $path ?: '/';

        return $uri;
    }

    public function withQuery($query) {
        if (is_array($query)) {
            $query = http_build_query($query);
        }

        $uri = clone $this;
        $uri->query = $query ?: '';

        return $uri;
    }

    public function withFragment($fragment) {
        if (!is_string($fragment)) {
            throw new \InvalidArgumentException('Invalid URI fragment');
        }

        $uri = clone $this;
        $uri->fragment = $fragment;

        return $uri;
    }

    public function __toString() {
        $uri = '';

        if ($scheme = $this->getScheme()) {
            $uri = $scheme.'://';
        }

        if ($authority = $this->getAuthority()) {
            $uri .= $authority;
        } else {
            $uri = '';
        }

        $uri .= $this->getPath();

        if ($query = $this->getQuery()) {
            $uri .= '?'.$query;
        }

        $fragment = $this->getFragment();
        if ($fragment !== '') {
            $uri .= '#'.$fragment;
        }

        return $uri;
    }
}
