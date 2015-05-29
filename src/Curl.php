<?php
namespace Owl;

if (!extension_loaded('curl')) {
    throw new \Exception('require "curl" extension');
}

class Curl {
    protected $handler;
    protected $options = [];

    public function __destruct() {
        $this->close();
    }

    public function close() {
        if ($this->handler) {
            curl_close($this->handler);
            $this->handler = null;
        }
        return $this;
    }

    public function setOptions(array $options) {
        foreach ($options as $key => $val) {
            $this->options[$key] = $val;
        }

        return $this;
    }

    public function execute($url, array $options = []) {
        $this->close();

        $curl_options = $this->options;
        foreach ($options as $key => $val) {
            $curl_options[$key] = $val;
        }
        $curl_options[CURLOPT_URL] = $url;

        $handler = curl_init();

        curl_setopt_array($handler, $curl_options);

        $result = curl_exec($handler);
        if ($result === false) {
            throw new \Exception('Curl Error: '. curl_error($handler), curl_errno($handler));
        }

        $this->handler = $handler;

        return $result;
    }

    public function getInfo($key = null) {
        if (!$this->handler) {
            return false;
        }

        return ($key === null)
             ? curl_getinfo($this->handler)
             : curl_getinfo($this->handler, $key);
    }
}
