<?php
namespace Sensor;
class BatchConsumer extends AbstractConsumer
{

    private $_buffers;
    private $_max_size;
    private $_url_prefix;
    private $_request_timeout;

    /**
     * @param string $url_prefix 服务器的 URL 地址。
     * @param int $max_size 批量发送的阈值。
     * @param int $request_timeout 请求服务器的超时时间，单位毫秒。
     */
    public function __construct($url_prefix, $max_size = 50, $request_timeout = 1000)
    {
        $this->_buffers = array();
        $this->_max_size = $max_size;
        $this->_url_prefix = $url_prefix;
        $this->_request_timeout = $request_timeout;
    }

    public function send($msg)
    {
        $this->_buffers[] = $msg;
        if (count($this->_buffers) >= $this->_max_size) {
            return $this->flush();
        }
        return true;
    }

    public function flush()
    {
        $ret = $this->_do_request(array(
            "data_list" => $this->_encode_msg_list($this->_buffers),
            "gzip" => 1
        ));
        if ($ret) {
            $this->_buffers = array();
        }
        return $ret;
    }

    /**
     * 发送数据包给远程服务器。
     *
     * @param array $data
     * @return bool 请求是否成功
     */
    protected function _do_request($data)
    {
        $params = array();
        foreach ($data as $key => $value) {
            $params[] = $key . '=' . urlencode($value);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->_url_prefix);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $this->_request_timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->_request_timeout);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, implode('&', $params));
        curl_setopt($ch, CURLOPT_USERAGENT, "PHP SDK");
        $ret = curl_exec($ch);

        if (false === $ret) {
            curl_close($ch);
            return false;
        } else {
            curl_close($ch);
            return true;
        }
    }

    /**
     * 对待发送的数据进行编码
     *
     * @param string $msg_list
     * @return string
     */
    private function _encode_msg_list($msg_list)
    {
        return base64_encode($this->_gzip_string("[" . implode(",", $msg_list) . "]"));
    }

    /**
     * GZIP 压缩一个字符串
     *
     * @param string $data
     * @return string
     */
    private function _gzip_string($data)
    {
        return gzencode($data);
    }

    public function close()
    {
        return $this->flush();
    }
}