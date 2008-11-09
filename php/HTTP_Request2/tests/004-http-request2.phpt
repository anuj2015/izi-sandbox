--TEST--
HTTP_Request2: test file uploads
--FILE--
<?php

require_once 'HTTP/Request2.php';
$request = new HTTP_Request2('http://dev.izimobil.org/test_server/test_upload.php', 'post');
$request->addPostParameter('foo', 'bar');
$request->addFileUpload('txt', dirname(__FILE__) . '/data/test.txt');
$request->addFileUpload('img', dirname(__FILE__) . '/data/image.gif');
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
Array
(
    [foo] => bar
)
Array
(
    [txt] => Array
        (
            [name] => test.txt
            [type] => text/plain
            [tmp_name] => %s
            [error] => 0
            [size] => 14052
        )

    [img] => Array
        (
            [name] => image.gif
            [type] => image/gif
            [tmp_name] => %s
            [error] => 0
            [size] => 1114
        )

)
</body>
</html>
