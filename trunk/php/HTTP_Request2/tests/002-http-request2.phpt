--TEST--
HTTP_Request2: test GET method
--FILE--
<?php

require_once 'HTTP/Request2.php';

$request = new HTTP_Request2('http://dev.izimobil.org/test_server/test_get.php', 'get');
$request->addQueryParameter('movie', 'Un chien Andalou');
$request->addQueryParameter('director', 'Luis Buñuel');
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
I worked !
<pre>
Array
(
    [movie] => Un chien Andalou
    [director] => Luis Buñuel
)
</pre>
Here are some utf8 chars:
ABCDEFGHIJKLMNOPQRSTUVWXYZ /0123456789
abcdefghijklmnopqrstuvwxyz £©µÀÆÖÞßéöÿ
–—‘“”„†•…‰™œŠŸž€ ΑΒΓΔΩαβγδω АБВГДабвгд
∀∂∈ℝ∧∪≡∞ ↑↗↨↻⇣ ┐┼╔╘░►☺♀ ﬁ�⑀₂ἠḂӥẄɐː⍎אԱა
</body>
</html>
