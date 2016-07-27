# api2captcha
Use api2captcha to bypass captcha. This api support on 2captcha.com.

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
