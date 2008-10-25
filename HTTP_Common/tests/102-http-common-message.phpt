--TEST--
HTTP_Common_Message: test getAllHeaders()
--FILE--
<?php

require_once 'HTTP/Common/Message.php';

class HTTP_CustomMessage extends HTTP_Common_Message
{
    public function __construct()
    {
        $this->defaultHeaders['x-foo'] = 'bar';
        $this->defaultHeaders['x-bar'] = 'foo';
    }
}

$message = new HTTP_CustomMessage();
$message->headers = array('connection'=>'close');
$message->headers['user-agent'] = 'phpt test !';
$message->headers['x-foo'] = 'foooooo!';
echo $message->getAllHeaders();

?>
--EXPECTF--
Connection: close
Date: %s +0000
User-Agent: phpt test !
X-Bar: foo
X-Foo: foooooo!
