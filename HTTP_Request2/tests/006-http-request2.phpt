--TEST--
HTTP_Request2: test POST method
--FILE--
<?php

require_once 'HTTP/Request2.php';

class MyObserver implements splObserver
{
    function update(splSubject $subject)
    {
        switch ($subject->state) {
        case HTTP_Request2::STATE_CONNECTED:
            echo ">>> Connected\n";
            break;
        case HTTP_Request2::STATE_REQUEST_SENT:
            echo ">>> Request sent\n";
            break;
        case HTTP_Request2::STATE_RESPONSE_STATUS:
            echo ">>> Got response status: ";
            echo $subject->data . "\n";
            break;
        case HTTP_Request2::STATE_RESPONSE_HEADERS:
            echo ">>> Got response headers: \n";
            echo $subject->data . "\n";
            break;
        case HTTP_Request2::STATE_RESPONSE_TICK:
            echo ">>> Got response body line: ";
            echo $subject->data . "\n";
            break;
        case HTTP_Request2::STATE_RESPONSE_RECEIVED:
            echo ">>> End of response\n";
            break;
        case HTTP_Request2::STATE_DISCONNECTED:
            echo ">>> Disconnected\n";
            break;
        case HTTP_Request2::STATE_DISCONNECTED:
            echo ">>> Got error: ";
            echo $subject->data->getMessage() . "\n";
            break;
        }
    }
}

$observer = new MyObserver();
$request  = new HTTP_Request2('http://dev.izimobil.org/test_server/test_post.php', 'post');
$request->headers['Accept-Encoding'] = 'entity';
$request->attach($observer);
$request->addPostParameter('foo', 'bar');
$request->addPostParameter('baz[0]', 'eggs');
$request->addPostParameter('baz[1]', '한국어');
$request->send();

?>
--EXPECTF--
>>> Connected
>>> Request sent
>>> Got response status: 200
>>> Got response headers: 
Date: %s
Content-Length: 459
Content-Type: text/html
Server: Apache/2.2.8 (Unix) mod_ssl/2.2.8 OpenSSL/0.9.7j PHP/5.2.6 mod_wsgi/2.0 Python/2.5.2
Vary: Accept-Encoding
X-Powered: PHP/5.2.6
>>> Got response body line: <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
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
>>> End of response
