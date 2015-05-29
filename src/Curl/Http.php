<?php
namespace Owl\Curl;

class Http extends \Owl\Curl {
    static public $method_emulate = true;

    public function head($url, array $params = []) {
        return $this->send($url, 'HEAD', $params);
    }

    public function get($url, array $params = []) {
        return $this->send($url, 'GET', $params);
    }

    public function post($url, array $params = []) {
        return $this->send($url, 'POST', $params);
    }

    public function put($url, array $params = []) {
        return $this->send($url, 'PUT', $params);
    }

    public function delete($url, array $params = []) {
        return $this->send($url, 'DELETE', $params);
    }

    protected function send($url, $method, array $params) {
        $method = strtoupper($method);

        $is_upload = false;
        foreach ($params as $key => $value) {
            if ($value instanceof \CURLFile) {
                $is_upload = true;
                break;
            }
        }

        // 数组必须用http_build_query转换为字符串
        // 否则会使用multipart/form-data而不是application/x-www-form-urlencoded
        if (!$is_upload) {
            $params = http_build_query($params) ?: null;
        }

        $options = [];

        if ($method == 'GET' || $method == 'HEAD') {
            if ($params) {
                $url = strpos($url, '?')
                     ? $url .'&'. $params
                     : $url .'?'. $params;
            }

            if ($method == 'GET') {
                $options[CURLOPT_HTTPGET] = true;
            } else {
                $options[CURLOPT_CUSTOMREQUEST] = 'HEAD';
                $options[CURLOPT_NOBODY] = true;
            }
        } else {
            if ($method == 'POST') {
                $options[CURLOPT_POST] = true;
            } elseif (static::$method_emulate) {
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_HTTPHEADER][] = 'X-HTTP-METHOD-OVERRIDE: '. $method;
                $options[CURLOPT_POSTFIELDS] = $params;
            } else {
                $options[CURLOPT_CUSTOMREQUEST] = $method;
            }

            if ($params)
                $options[CURLOPT_POSTFIELDS] = $params;
        }

        $options[CURLOPT_RETURNTRANSFER] = true;
        $options[CURLOPT_HEADER] = true;

        $result = $this->execute($url, $options);

        $message = array();
        $message['info'] = $this->getInfo();

        $header_size = $message['info']['header_size'];
        $message['header'] = preg_split('/\r\n/', substr($result, 0, $header_size), 0, PREG_SPLIT_NO_EMPTY);
        $message['body'] = substr($result, $header_size);

        return $message;
    }
}
