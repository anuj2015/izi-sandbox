--TEST--
HTTP_Common_Headers: test iteration styles
--FILE--
<?php

require_once 'HTTP/Common/Headers.php';

$headers = new HTTP_Common_Headers(array(
    'Content-encoding' => 'gzip,deflate,compress',
    'connection'       => 'close',
    'User-aGENT'       => 'phpt test !',
));

echo "camelcase:\n";
foreach ($headers as $k => $v) {
    echo "$k: $v\n";
}
echo "\n";
$headers->iterationStyle = 'lowercase';
echo "lowercase:\n";
foreach ($headers as $k => $v) {
    echo "$k: $v\n";
}

?>
--EXPECT--
camelcase:
Content-Encoding: gzip,deflate,compress
Connection: close
User-Agent: phpt test !

lowercase:
content-encoding: gzip,deflate,compress
connection: close
user-agent: phpt test !
