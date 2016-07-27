<?php
$c = new Api2Captcha('API KEY');
$c->decode('1469438602.png');
echo $c->result();
echo $c->error();
?>
