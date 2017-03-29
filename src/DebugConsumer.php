<?php
namespace Sensor;
class DebugConsumer extends AbstractConsumer
{

    private $_debug_url_prefix;
    private $_request_timeout;
    private $_debug_write_data;

    /**
     * DebugConsumer constructor,用于调试模式.
     * 具体说明可以参照:http://www.sensorsdata.cn/manual/debug_mode.html
     *
     * @param string $url_prefix 服务器的URL地址
     * @param bool $write_data 是否把发送的数据真正写入
     * @param int $request_timeout 请求服务器的超时时间,单位毫秒.
     * @throws SensorsAnalyticsDebugException
     */
    public function __construct($url_prefix, $write_data = True, $request_timeout = 1000)
    {
        $parsed_url = parse_url($url_prefix);
        if ($parsed_url === false) {
            throw new SensorsAnalyticsDebugException("Invalid server url of Sensors Analytics.");
        }

        // 将 URI Path 替换成 Debug 模式的 '/debug'
        $parsed_url['path'] = '/debug';

        $this->_debug_url_prefix = ((isset($parsed_url['scheme'])) ? $parsed_url['scheme'] . '://' : '')
            . ((isset($parsed_url['user'])) ? $parsed_url['user'] . ((isset($parsed_url['pass'])) ? ':' . $parsed_url['pass'] : '') . '@' : '')
            . ((isset($parsed_url['host'])) ? $parsed_url['host'] : '')
            . ((isset($parsed_url['port'])) ? ':' . $parsed_url['port'] : '')
            . ((isset($parsed_url['path'])) ? $parsed_url['path'] : '')
            . ((isset($parsed_url['query'])) ? '?' . $parsed_url['query'] : '')
            . ((isset($parsed_url['fragment'])) ? '#' . $parsed_url['fragment'] : '');

        $this->_request_timeout = $request_timeout;
        $this->_debug_write_data = $write_data;

    }

    public function send($msg)
    {
        $buffers = array();
        $buffers[] = $msg;
        $response = $this->_do_request(array(
            "data_list" => $this->_encode_msg_list($buffers),
            "gzip" => 1
        ));
        printf("\n=========================================================================\n");
        if ($response['ret_code'] === 200) {
            printf("valid message: %s\n", $msg);
        } else {
            printf("invalid message: %s\n", $msg);
            printf("ret_code: %d\n", $response['ret_code']);
            printf("ret_content: %s\n", $response['ret_content']);
        }

        if ($response['ret_code'] >= 300) {
            throw new SensorsAnalyticsDebugException("Unexpected response from SensorsAnalytics.");
        }
    }

    /**
     * 发送数据包给远程服务器。
     *
     * @param array $data
     * @return array
     * @throws SensorsAnalyticsDebugException
     */
    protected function _do_request($data)
    {
        $params = array();
        foreach ($data as $key => $value) {
            $params[] = $key . '=' . urlencode($value);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $this->_debug_url_prefix);
        if ($this->_debug_write_data === false) {
            // 这个参数为 false, 说明只需要校验,不需要真正写入
            print("\ntry Dry-Run\n");
            curl_setopt($ch, CURLOPT_HTTPHEADER, Array(
                "Dry-Run:true"
            ));


        }
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $this->_request_timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->_request_timeout);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, implode('&', $params));
        curl_setopt($ch, CURLOPT_USERAGENT, "PHP SDK");

        $http_response_header = curl_exec($ch);
        if (!$http_response_header) {
            throw new SensorsAnalyticsDebugException(
                "Failed to connect to SensorsAnalytics. [error='" + curl_error($ch) + "']");
        }

        $result = array(
            "ret_content" => $http_response_header,
            "ret_code" => curl_getinfo($ch, CURLINFO_HTTP_CODE)
        );
        curl_close($ch);
        return $result;
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
}