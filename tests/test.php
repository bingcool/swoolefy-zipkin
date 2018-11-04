<?php

require_once "../vendor/autoload.php";

use zipkin\ZipkinHander;

$zipkin = ZipkinHander::getInstance('http://123.207.19.149', '9411', '/api/v1/spans');

// 当前服务的信息
$zipkin->setEndpoint('First service', '192.168.99.103', 80);

//采样率设置必须在setTracer函数之前设置才有效,90代表90%采样率，根前端才需要设置
$zipkin->setPercentageSampler($percents = 100);
// 如果是根span
$zipkin->setTracer('/First/test',true);
// 如果是下级span,也就是后端
// $zipkin->setTracer('/Test/test', false);

//这里开始创建一个span 
$begainSpanInfo = $zipkin->begainSpan();
list($requireStartTime, $spanId) = $begainSpanInfo;

$url = 'https://www.baidu.com';
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
$request = file_get_contents($url, true, $context);

// 这里结束一个span
$zipkin->afterSpan($begainSpanInfo, ['jsonplaceholder API', '104.31.87.157', '80'], 'jsonplaceholder API');


//这里开始创建一个span 
$begainSpanInfo = $zipkin->begainSpan();
list($requireStartTime, $spanId) = $begainSpanInfo;

$url = 'http://www.swoolefy.com/Test/testajax';
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
	
$request = file_get_contents($url, true, $context);

// 这里结束一个span
$zipkin->afterSpan($begainSpanInfo, ['swoolefy API', '192.168.99.103', '81'], 'swoolefy API');

//在 php fpm中直接
$zipkin->trace(true);
//在swoolefy中
// $app = \Swoolefy\Core\Application::getApp();
// $app->afterRequest(function() use($zipkin) {
// 	$zipkin->trace(true);
// });
