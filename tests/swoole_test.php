<?php

require_once "../vendor/autoload.php";

use zipkin\ZipkinHander;

$zipkin = ZipkinHander::getInstance('http://192.168.99.103', '9507', '/api/v1/spans', true);

// 当前服务的信息
$zipkin->setEndpoint('swoolefy service1', '192.168.99.102', 80);

//采样率设置必须在setTracer函数之前设置才有效,90代表90%采样率，根前端才需要设置,swoolefy中一般作为后端调用，不不用设置
//$zipkin->setPercentageSampler($percents = 90);
// 如果是根span,在swoolefy环境中需要将app应用实例注入
$zipkin->setTracer('/Test/test', false, \Swoolefy\Core\Application::getApp());
// 如果是下级span,也就是后端
// $zipkin->setTracer('/Test/test', true);

//这里开始创建一个span 
$begainSpanInfo = $zipkin->begainSpan();
list($requireStartTime, $spanId) = $begainSpanInfo;

$url = 'https://jsonplaceholder.typicode.com/posts/1';
	$context = stream_context_create([
	    'http' => [
	        'method' => 'GET',
	        'header' =>
	            'X-B3-TraceId: ' . $zipkin->getTraceId() . "\r\n" .
	            'X-B3-SpanId: ' . ((string) $spanId) . "\r\n" .
	            'X-B3-ParentSpanId: ' . $zipkin->getTraceSpanId() . "\r\n" .
	            'X-B3-Sampled: ' . $zipkin->isSampled() . "\r\n"
	    ]
	]);

$request = file_get_contents($url, false, $context);

// 这里结束一个span
$zipkin->afterSpan($begainSpanInfo, ['jsonplaceholder API', '104.31.87.157', '80'], 'posts/1');


//在 php fpm中直接
//$zipkin->trace(true);
//在swoolefy中
$app = \Swoolefy\Core\Application::getApp();
$app->afterRequest(function() use($zipkin) {
	$zipkin->trace(true);
});
