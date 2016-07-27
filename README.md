# api2captcha
Use 2captcha.com to bypass captcha

#Use Guide
```
<?php
include 'api2captcha.php';
$c = new Api2Captcha('APIKEY HERE');
$c->decode('PATH TO CAPTCHA IMAGE');
echo $c->result(); #This is text on captcha image
echo "<br/>";
echo $c->error();
?>
```

Contact me: [Nhân Châu KP](https://www.facebook.com/pronhan95)
