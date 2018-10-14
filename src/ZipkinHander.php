<?php
namespace zipkin;

use zipkin\ZipkinTracer;
use zipkin\ZipkinHttpLogger;
use whitemerry\phpkin\Tracer;
use whitemerry\phpkin\Span;
use whitemerry\phpkin\Endpoint;
use whitemerry\phpkin\Metadata;
use whitemerry\phpkin\TracerInfo;
use whitemerry\phpkin\AnnotationBlock;
use whitemerry\phpkin\Logger\LoggerException;
use whitemerry\phpkin\Identifier\SpanIdentifier;
use whitemerry\phpkin\Identifier\TraceIdentifier;

class ZipkinHander {

		/**
		 * $cid 协程id
		 * @var null
		 */
		private $cid = null;

		/**
		 * $instance
		 * @var [type]
		 */
		private static $instance;

	    /**
	     * $logger 日志发送对象
	     * @var null
	     */
	    public $logger = null;

	    /**
	     * $endpoint 
	     * @var null
	     */
	    public $endpoint = null;

	    /**
	     * $tracer
	     * @var null
	     */
	    public $tracer = null;

	    /**
	     * $percentageSampler 采样率
	     * @var array
	     */
	    public $percentageSampler = ['percents'=>100];

	    /**
		 * getInstance 
		 * @param    $args
		 * @return   mixed
		 */
	    public static function getInstance(...$args) {
	    	$cid = 'cid_00';
	    	if(class_exists('co')) {
	    		$cid = \co::getuid();
		    	if($cid > 0) {
		    		$cid = 'cid_'.$cid;
		    	}
	    	}
	        if(!isset(self::$instance[$cid])){
	            self::$instance[$cid] = new static(...$args);
	            self::$instance[$cid]->setCid($cid);
	        }
	        return self::$instance[$cid];
	    }

		/**
		 * __construct 
		 */
		protected function __construct($zipkin_host, $zipkin_port = 80, $muteErrors = true, $contextOptions = [], $endpoint = '/api/v1/spans') {
			if(strpos($zipkin_host, 'http://') !== false || strpos($zipkin_host, 'https://') !== false) {
				$this->logger= new ZipkinHttpLogger(['host' => $zipkin_host.":".$zipkin_port, 'endpoint'=>$endpoint, 'muteErrors' => $muteErrors, 'contextOptions'=>$contextOptions]);
			}else {
				throw new LoggerException('zipkin_host require a scheme of http or https');
			}
		}

		/**
		 * setEndpoint 创建本地的服务端
		 * @param    string  $local_servicename
		 * @param    string  $local_ip
		 * @param    int     $port
		 */
		public function setEndpoint($local_servicename, $local_ip, $local_port) {
			$this->endpoint = new Endpoint($local_servicename, $local_ip, $local_port);
		}


		/**
		 * setTracer 创建追踪实例
		 * @param 本次请求的
		 */
		public function setTracer($local_span, $is_back = false) {
			/**
		 	* Read headers
		 	*/
			$traceId = null;

			if(!empty($_SERVER['HTTP_X_B3_TRACEID'])) {
			    $traceId = new TraceIdentifier($_SERVER['HTTP_X_B3_TRACEID']);
			}

			$traceSpanId = null;
			if (!empty($_SERVER['HTTP_X_B3_SPANID'])) {
			    $traceSpanId = new SpanIdentifier($_SERVER['HTTP_X_B3_SPANID']);
			}

			$isSampled = null;
			if(!empty($_SERVER['HTTP_X_B3_SAMPLED'])) {
			    $isSampled = (bool) $_SERVER['HTTP_X_B3_SAMPLED'];
			}else {
				// 根前端设置采样率
				$isSampled = new \whitemerry\phpkin\Sampler\PercentageSampler($this->percentageSampler);
			}

			$this->tracer = new ZipkinTracer($local_span, $this->endpoint, $this->logger, $isSampled, $traceId, $traceSpanId);

			!$is_back && $this->tracer->setProfile(Tracer::BACKEND);

			$this->is_back = $is_back;

		}

		/**
		 * setPercentageSampler 设置采样率
		 * @param array $percents
		 */
		public function setPercentageSampler($percents = 100) {
			$this->percentageSampler = ['percents'=>$percents];
		}

		/**
		 * setCid
		 * @param $cid
		 */
		public function setCid($cid) {
			$this->cid = $cid;
		}

		/**
		 * getCid
		 * @return int
		 */
		public function getCid() {
			return $this->cid;
		}

		/**
		 * isFrontend 
		 * @return boolean
		 */
		public function isFrontend() {
			if(!$this->is_back) {
				return 1;
			}
			return 0;
		}
		
		/**
		 * begainSpan 
		 * @return  mixed
		 */
		public function begainSpan() {
			$requestStart = zipkin_timestamp();
			$spanId = new SpanIdentifier();
			return [$requestStart, $spanId];
		}


		/**
		 * afterSpan 
		 * @param    array  $begainSpanInfo
		 * @param    array  $remote_endpoint_info
		 * @param    string $remote_span_name
		 * @return   void
		 */
		public function afterSpan(array $begainSpanInfo, array $remote_endpoint_info, string $remote_span_name, array $binaryAnnotations = []) {
			list($requestStart, $spanId) = $begainSpanInfo;

			list($remote_endpoint_servicename, $ip, $port) = $remote_endpoint_info;
			
			$endpoint = new Endpoint($remote_endpoint_servicename, $ip, $port);

			$annotationBlock = new AnnotationBlock($endpoint, $requestStart);

			$meta = new Metadata();

			if(!empty($binaryAnnotations)) {
				foreach($binaryAnnotations as $key=>$value) {
					$meta->set($key, $value);
				}
			}

			$span = new Span($spanId, $remote_span_name, $annotationBlock, $meta);
			// Add span to Zipkin
			$this->tracer->addSpan($span);
		}

		/**
		 * getTraceId 获取追踪id
		 * @return   string
		 */
		public function getTraceId() {
			return TracerInfo::getTraceId();
		}

		/**
		 * getTraceSpanId 
		 * @return   获取当前创建的spanId
		 */
		public function getTraceSpanId() {
			return TracerInfo::getTraceSpanId();
		}

		/**
		 * isSampled 判断是否采样
		 * @return   
		 */
		public function isSampled() {
			return (int)TracerInfo::isSampled();
		}

		/**
		 * trace 
		 * @param    boolean   $is_async  是否异步发送
		 * @return   
		 */
		public function trace($is_async = false) {
			$this->tracer->trace($is_async);
			unset(self::$instance[$this->cid]);
		}

		/**
		 * __destruct 
		 * @param   
		 */
		public function __destruct() {
			self::$instance = null;
		}

}