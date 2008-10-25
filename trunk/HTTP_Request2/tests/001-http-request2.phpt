--TEST--
HTTP_Request2: test various constructs and __toString() method
--FILE--
<?php

require_once 'HTTP/Request2.php';

$request = new HTTP_Request2('http://www.example.com');
echo $request;

$request = new HTTP_Request2('http://www.example.com/index.php?foo=bar&baz=foo#test');
$request->httpVersion = HTTP_Request2::HTTP_VERSION_1_0;
echo $request;

$request = new HTTP_Request2('http://www.example.com/index.php', 'POST');
$request->headers['user-agent'] = 'foo';
echo $request;

$request = new HTTP_Request2('http://www.example.com/index.php', 'HEAD');
echo $request;

$request = new HTTP_Request2('http://www.example.com/index.php', 'delete');
echo $request;

$request = new HTTP_Request2('http://www.example.com/index.php', 'TRACE');
echo $request;

$request = new HTTP_Request2('http://www.example.com/index.php', 'connect');
echo $request;

?>
--EXPECTF--
GET / HTTP/1.1
Host: www.example.com:80
Date: %s
Accept-Encoding: gzip,deflate
Content-Length: 0
User-Agent: PEAR HTTP_Request2/@package_version@

GET http://www.example.com/index.php?foo=bar&baz=foo#test HTTP/1.0
Date: %s
Accept-Encoding: gzip,deflate
Content-Length: 0
User-Agent: PEAR HTTP_Request2/@package_version@

POST /index.php HTTP/1.1
Host: www.example.com:80
Date: %s
Accept-Encoding: gzip,deflate
Content-Length: 0
User-Agent: foo

HEAD /index.php HTTP/1.1
Host: www.example.com:80
Date: %s
Accept-Encoding: gzip,deflate
Content-Length: 0
User-Agent: PEAR HTTP_Request2/@package_version@

DELETE /index.php HTTP/1.1
Host: www.example.com:80
Date: %s
Accept-Encoding: gzip,deflate
Content-Length: 0
User-Agent: PEAR HTTP_Request2/@package_version@

TRACE /index.php HTTP/1.1
Host: www.example.com:80
Date: %s
Accept-Encoding: gzip,deflate
Content-Length: 0
User-Agent: PEAR HTTP_Request2/@package_version@

CONNECT /index.php HTTP/1.1
Host: www.example.com:80
Date: %s
Accept-Encoding: gzip,deflate
Content-Length: 0
User-Agent: PEAR HTTP_Request2/@package_version@
