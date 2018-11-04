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
        if(extension_loaded('swoole') && defined('SWOOLEFY_ENV')) {
            $cli = new \Swoole\Coroutine\Http\Client($this->zipkin_ip, $this->zipkin_port);
            $cli->set([ 'timeout' => 1]);
            $cli->setHeaders([
                'Content-type'=>'application/json',            
            ]);
            $cli->post($this->options['endpoint'], json_encode($spans));
            $cli->close();
        }else {
            $this->trace($spans);
        }   
    }
}