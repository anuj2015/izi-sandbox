--TEST--
HTTP_Common_Headers: test __toString() method
--FILE--
<?php

require_once 'HTTP/Common/Headers.php';

$headers = new HTTP_Common_Headers(array(
    'Content-encoding' => 'gzip,deflate,compress',
    'connection'       => 'close',
    'User-aGENT'       => 'phpt test !',
));

echo $headers;

?>
--EXPECT--
Connection: close
Content-Encoding: gzip,deflate,compress
User-Agent: phpt test !
