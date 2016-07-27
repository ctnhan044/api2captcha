# api2captcha
Use 2captcha.com to bypass captcha

#Use Guide
#Include file
include 'api2captcha.php';

#Call object
$c = new Api2Captcha('APIKEY HERE');
#Decode
$c->decode('PATH TO CAPTCHA IMAGE');
#Result
echo $c->result();
echo "<br/>";
echo $c->error();

