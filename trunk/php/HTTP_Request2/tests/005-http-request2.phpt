--TEST--
HTTP_Request2: test HEAD method
--FILE--
<?php

require_once 'HTTP/Request2.php';
$request = new HTTP_Request2('http://dev.izimobil.org/test_server/test_get.php', 'head');
echo $request->send();

?>
--EXPECTF--
HTTP/1.1 200 OK
Date: %s
Content-Encoding: gzip
Content-Length: 20
Content-Type: text/html
Server: Apache/2.2.8 (Unix) mod_ssl/2.2.8 OpenSSL/0.9.7j PHP/5.2.6 mod_wsgi/2.0 Python/2.5.2
Vary: Accept-Encoding
X-Powered: PHP/5.2.6

