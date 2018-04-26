<?php

require_once "../vendor/autoload.php";

use zipkin\ZipkinHander;

$zipkin = ZipkinHander::getInstance('http://192.168.99.102', '9411', false);

$zipkin->setEndpoint('swoolefy service1', '192.168.99.102', 80);

// 如果是根span
$zipkin->setTracer('/Test/test');
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



$zipkin->trace(true);
