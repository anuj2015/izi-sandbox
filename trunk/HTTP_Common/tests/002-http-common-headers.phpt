--TEST--
HTTP_Common_Headers: test array access
--FILE--
<?php

require_once 'HTTP/Common/Headers.php';

$headers = new HTTP_Common_Headers();

// test offsetSet()
$headers['Content-ENCODING'] = 'gzip,deflate,compress';
$headers['connection']       = 'close';
$headers['user-agent']       = 'phpt test !';
$headers['x-foo']            = array('foo', 'bar');
$headers['x-foo']            = 'baz';
$headers['x-baz']            = 0;

// test offsetGet()
echo $headers['content-encoding'] . "\n";
echo $headers['CONNECTION'] . "\n";
echo $headers['User-Agent'] . "\n";
echo $headers['x-foo'] . "\n";
echo $headers['x-baz'] . "\n";

// test offsetUnset()
unset($headers['USER-AGENT']);
unset($headers['X-Foo']);

// test offsetExists()
var_dump(isset($headers['user-agent']));
var_dump(isset($headers['Connection']));
var_dump(isset($headers['x-baz']));

// test count()
var_dump(count($headers));
unset($headers['Connection']);
unset($headers['Content-Encoding']);
unset($headers['x-baz']);
var_dump(count($headers));

?>
--EXPECT--
gzip,deflate,compress
close
phpt test !
foo,bar,baz
0
bool(false)
bool(true)
bool(true)
int(3)
int(0)
