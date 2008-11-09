--TEST--
HTTP_Request2: test POST method
--FILE--
<?php

require_once 'HTTP/Request2.php';

$request = new HTTP_Request2('http://dev.izimobil.org/test_server/test_post.php', 'post');
$request->addPostParameter('foo', 'bar');
$request->addPostParameter('baz[0]', 'eggs');
$request->addPostParameter('baz[1]', '한국어');
$response = $request->send();
echo $response->getBody();

?>
--EXPECTF--
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
 <meta content="text/html; charset=UTF-8" http-equiv="Content-Type"/>
 <title>HTTP_Request2 test page</title>
</head>

<body>
<pre>
Array
(
    [foo] => bar
    [baz] => Array
        (
            [0] => eggs
            [1] => 한국어
        )

)
</pre>
</body>
</html>
