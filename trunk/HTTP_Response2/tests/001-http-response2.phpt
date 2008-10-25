--TEST--
HTTP_Response
--FILE--
<?php

require_once 'HTTP/Response2.php';

$res = '
HTTP/1.1 200 OK
Content-Type: text/html; charset=utf-8
Content-Length: length

<?xml encoding="utf-8"?>
<root>
    <foo>foo</foo>
    <bar>bar</bar>
    <utf8>Pace سلام שלום Hasîtî 和平</utf8>
</root>';

$response = HTTP_Response2::fromString($res);
echo $response;

?>
--EXPECT--
HTTP/1.1 200 OK
Content-Length: length
Content-Type: text/html; charset=utf-8

<?xml encoding="utf-8"?>
<root>
    <foo>foo</foo>
    <bar>bar</bar>
    <utf8>Pace سلام שלום Hasîtî 和平</utf8>
</root>
