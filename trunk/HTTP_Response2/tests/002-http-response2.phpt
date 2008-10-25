--TEST--
HTTP_Response
--FILE--
<?php

require_once 'HTTP/Response2.php';

$response = new HTTP_Response2(400, 'Foo !');
$response->headers['connection'] = 'close';
$response->headers['server'] = 'phpt test !';
echo $response;

--EXPECT--
HTTP/1.1 400 Bad Request
Connection: close
Server: phpt test !

Foo !
