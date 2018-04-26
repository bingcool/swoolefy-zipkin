<?php
namespace zipkin;

use whitemerry\phpkin\Logger\SimpleHttpLogger;

class ZipkinHttpLogger extends SimpleHttpLogger {

    public $zipkin_ip = null;

    public $zipkin_port = 80;

    public function __construct($options = []) {
        parent::__construct($options);

        $http_info = parse_url($this->options['host']);

        $this->zipkin_ip = $http_info['host'];
        $this->zipkin_port = $http_info['port'];

    }
	
    /**
     * asyncTrace 异步发送至zipkin平台
     * @param    $spans
     * @return   
     */
    public function asyncTrace($spans) {
        if(extension_loaded('swoole')) {
            $cli = new \swoole_http_client($this->zipkin_ip, $this->zipkin_port);

            $cli->setHeaders([
                'header' => 'Content-type: application/json',
            ]);
            
            $cli->setData(json_encode($spans));

            $cli->post($this->options['endpoint'], [], function ($cli) {
            });
            return ;
        }else {
            // 同步
            $this->trace($spans);
        }
    }
}