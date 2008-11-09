--TEST--
HTTP_Response
--FILE--
<?php

require_once 'HTTP/Response2.php';

$res = '
HTTP/1.1 200 OK
Connection: close

';
$response = HTTP_Response2::fromString($res);
var_dump($response->shouldClose());

$res = '
HTTP/1.1 200 OK
Connection: keep-alive

';
$response = HTTP_Response2::fromString($res);
var_dump($response->shouldClose());

$res = '
HTTP/1.1 200 OK
Content-Length: 0

';
$response = HTTP_Response2::fromString($res);
var_dump($response->shouldClose());

$res = '
HTTP/1.0 200 OK
Content-Length: 0

';
$response = HTTP_Response2::fromString($res);
var_dump($response->shouldClose());

$res = '
HTTP/1.0 200 OK
Connection: keep-alive

';
$response = HTTP_Response2::fromString($res);
var_dump($response->shouldClose());

$res = '
HTTP/1.0 200 OK
Keep-Alive: some value

';
$response = HTTP_Response2::fromString($res);
var_dump($response->shouldClose());

$res = '
HTTP/1.0 200 OK
Proxy-Connection: Keep-Alive

';
$response = HTTP_Response2::fromString($res);
var_dump($response->shouldClose());

?>
--EXPECT--
bool(true)
bool(false)
bool(false)
bool(true)
bool(false)
bool(false)
bool(false)
