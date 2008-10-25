--TEST--
HTTP_Response
--FILE--
<?php

require_once 'HTTP/Response2.php';

$res = '
HTTP/1.1 200 OK
Content-Type: text/html; charset=utf-8
Content-Length: 0

';
$response = HTTP_Response2::fromString($res);
var_dump($response->isError());
var_dump($response->isRedirect());
var_dump($response->isSuccessful());
echo "\n";

$res = '
HTTP/1.1 301 Moved Permanently
Content-Length: 0

';
$response = HTTP_Response2::fromString($res);
var_dump($response->isError());
var_dump($response->isRedirect());
var_dump($response->isSuccessful());
echo "\n";

$res = '
HTTP/1.1 404 Not Found
Content-Length: 0

';
$response = HTTP_Response2::fromString($res);
var_dump($response->isError());
var_dump($response->isRedirect());
var_dump($response->isSuccessful());
echo "\n";

?>
--EXPECT--
bool(false)
bool(false)
bool(true)

bool(false)
bool(true)
bool(false)

bool(true)
bool(false)
bool(false)
