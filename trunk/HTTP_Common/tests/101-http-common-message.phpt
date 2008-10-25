--TEST--
HTTP_Common_Message: test message magic methods
--FILE--
<?php

require_once 'HTTP/Common/Message.php';

class HTTP_CustomMessage extends HTTP_Common_Message
{
}

$message = new HTTP_CustomMessage();
$message->httpVersion = HTTP_Common_Message::HTTP_VERSION_1_1;
$message->body = 'Foo!';
$message->headers = array('connection'=>'close');
$message->headers['user-agent'] = 'phpt test !';

echo $message->httpVersion . "\r\n";
echo $message->headers     . "\r\n\r\n";
echo $message->body        . "\r\n\r\n";

require_once 'HTTP/Common/Headers.php';

$message = new HTTP_CustomMessage();
$message->httpVersion = HTTP_Common_Message::HTTP_VERSION_1_0;
$message->body = 'Bar!';
$message->headers = new HTTP_Common_Headers(
    array('connection'=>'close', 'user-agent'=>'phpt test !')
);

echo $message->httpVersion . "\r\n";
echo $message->headers     . "\r\n\r\n";
echo $message->body        . "\r\n\r\n";

?>
--EXPECTF--
HTTP/1.1
Connection: close
User-Agent: phpt test !

Foo!

HTTP/1.0
Connection: close
User-Agent: phpt test !

Bar!
