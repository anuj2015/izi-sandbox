--TEST--
HTTP_Common_Headers: test object access
--FILE--
<?php

require_once 'HTTP/Common/Headers.php';

$headers = new HTTP_Common_Headers();

// test __set()
$headers->{'Content-ENCODING'} = 'gzip,deflate,compress';
$headers->connection           = 'close';
$headers->{'user-agent'}       = 'phpt test !';
$headers->{'x-foo'}            = array('foo', 'bar');
$headers->{'x-foo'}            = 'baz';

// test __get()
echo $headers->{'content-encoding'} . "\n";
echo $headers->CONNECTION           . "\n";
echo $headers->{'User-Agent'}       . "\n";
echo $headers->{'x-Foo'}            . "\n";

// test __unset()
unset($headers->{'USER-AGENT'});
unset($headers->{'X-Foo'});

// test __isset()
var_dump(isset($headers->{'user-agent'}));
var_dump(isset($headers->Connection));

?>
--EXPECT--
gzip,deflate,compress
close
phpt test !
foo,bar,baz
bool(false)
bool(true)
