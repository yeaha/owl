<?php
namespace Owl\Http;

use Owl\Http\StringStream;

class Response implements \Psr\Http\Message\ResponseInterface {
    use \Owl\Http\MessageTrait;

    private $phrases = [
        // INFORMATIONAL CODES
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        // SUCCESS CODES
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        // REDIRECTION CODES
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy', // Deprecated
        307 => 'Temporary Redirect',
        // CLIENT ERROR
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        // SERVER ERROR
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];

    protected $code = 200;

    protected $reason_phrase = '';

    protected $cookies = [];

    protected $end = false;

    public function __construct() {
        $this->immutability = false;
        $this->reset();
    }

    public function reset() {
        $this->attributes = [];
        $this->code = 200;
        $this->cookies = [];
        $this->headers = [];
        $this->body = new StringStream;
        $this->end = false;
    }

    public function getStatusCode() {
        return $this->code;
    }

    public function withStatus($code, $reasonPhrase = '') {
        $this->code = (int)$code;
        $this->reason_phrase = $reasonPhrase;

        return $this;
    }

    public function getReasonPhrase() {
        if ($this->reason_phrase) {
            return $this->reason_phrase;
        }

        return isset($this->phrases[$this->code])
             ? $this->phrases[$this->code]
             : null;
    }

    public function getCookies() {
        return $this->cookies;
    }

    public function withCookie($name, $value, $expire = 0, $path = '/', $domain = null, $secure = null, $httponly = true) {
        if ($secure === null) {
            $secure = isset($_SERVER['HTTPS']) ? (bool)$_SERVER['HTTPS'] : false;
        }

        $key = sprintf('%s@%s:%s', $name, $domain, $path);
        $this->cookies[$key] = [$name, $value, $expire, $path, $domain, $secure, $httponly];
        return $this;
    }

    public function redirect($url, $status = 303) {
        return $this->withStatus($status)
                    ->withHeader('Location', $url);
    }

    public function write($data) {
        $this->getBody()->write($data);

        return $this;
    }

    public function end($data = null) {
        if ($this->end) {
            return $this;
        }

        $this->end = true;

        if ($data !== null) {
            $this->write($data);
        }

        $this->send();

        return $this;
    }

    protected function send() {
        if (!headers_sent()) {
            $code = $this->getStatusCode();
            $version = $this->getProtocolVersion();

            if ($code !== 200 || $version !== '1.1') {
                header(sprintf('HTTP/%s %d %s', $version, $code, $this->getReasonPhrase()));
            }

            foreach ($this->headers as $key => $value) {
                $key = ucwords(strtolower($key), '-');
                if (is_array($value)) {
                    $value = implode(',', $value);
                }

                header(sprintf('%s: %s', $key, $value));
            }

            foreach ($this->cookies as $cookie) {
                list($name, $value, $expire, $path, $domain, $secure, $httponly) = $cookie;
                setCookie($name, $value, $expire, $path, $domain, $secure, $httponly);
            }
        }

        $body = $this->getBody();
        echo $body->getContents();
    }

    /**
     * @deprecated
     */
    public function setStatus($status) {
        return $this->withStatus($status);
    }

    /**
     * @deprecated
     */
    public function getStatus() {
        return $this->getStatusCode();
    }

    /**
     * @deprecated
     */
    public function setHeader($key, $value) {
        return $this->withHeader($key, $value);
    }

    /**
     * @deprecated
     */
    public function setCookie($name, $value, $expire = 0, $path = '/', $domain = null, $secure = null, $httponly = true) {
        return $this->withCookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * @deprecated
     */
    public function setBody($body) {
        return $this->write($body);
    }

    /**
     * @deprecated
     */
    public function setParameter($key, $value) {
        return $this->withAttribute($key, $value);
    }

    /**
     * @deprecated
     */
    public function getParameter($key) {
        return $this->getAttribute($key);
    }

    /**
     * @deprecated
     */
    public function getParameters() {
        return $this->getAttributes();
    }
}
