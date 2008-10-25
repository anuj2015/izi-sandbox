--TEST--
HTTP_Common_Headers: test sort() method
--FILE--
<?php

require_once 'HTTP/Common/Headers.php';

$headers = new HTTP_Common_Headers(array(
    'User-aGENT'       => 'phpt test !',
    'X-Foo'            => 'bar',
    'Content-encoding' => 'gzip,deflate,compress',
    'content-length'   => 0,
    'date'             => '...',
    'connection'       => 'close',
));

echo $headers;

?>
--EXPECT--
Connection: close
Date: ...
Content-Encoding: gzip,deflate,compress
Content-Length: 0
User-Agent: phpt test !
X-Foo: bar
