<?php

class Api2Captcha {

    public $requestTimeout = 5;
    public $maxTimeout = 60;

    /**
     * 0 = 1 word (default value)
     * 1 = CAPTCHA contains 2 words
     * @var int 
     */
    public $isPhrase = 0;

    /**
     * 0 = not case sensitive (default value)
     * 1 = case sensitive 
     * @var int 
     */
    public $isRegSense = 0;

    /**
     * 0 = not specified (default value)
     * 1 = numeric CAPTCHA
     * 2 = letters CAPTCHA
     * 3 = either numeric or letters. 
     * @var int
     */
    public $isNumeric = 0;

    /**
     * 0 = not specified (default value)
     * 1..20 = minimal number of symbols in the CAPTCHA text
     * @var int 
     */
    public $minLen = 0;

    /**
     * 0 = not specified (default value)
     * 1..20 = maximal number of symbols in the CAPTCHA text
     * @var int
     */
    public $maxLen = 0;

    /**
     * 0 = not specified (default value)
     * 1 = Cyrillic CAPTCHA
     * 2 = Latin CAPTCHA
     * @var int 
     */
    public $language = 0;
    protected $apiKey;
    protected $result;
    protected $error;
    private $captcha_id;

    const API_IN_URL = 'http://2captcha.com/in.php';
    const API_RES_URL = 'http://2captcha.com/res.php';

    private $errors = array(
        'ERROR_NO_SLOT_AVAILABLE' => 'No free workers at the moment, try again later or promote your maximum bid here',
        'ERROR_ZERO_CAPTCHA_FILESIZE' => 'CAPTCHA size that you load less than 100 bytes',
        'ERROR_TOO_BIG_CAPTCHA_FILESIZE' => 'Your captcha is larger than 100 kilobytes',
        'ERROR_ZERO_BALANCE' => 'Zero or negative balance',
        'ERROR_IP_NOT_ALLOWED' => 'The request to this key with the current IP address rejected. Please see the section Access Control over IP',
        'ERROR_CAPTCHA_UNSOLVABLE' => 'Unable to solve the CAPTCHA',
        'ERROR_BAD_DUPLICATES' => 'Function 100% recognition did not work, and because of attempts to limit',
        'ERROR_NO_SUCH_METHOD' => 'You must send a method parameter in your request to the API, refer to the documentation',
        'ERROR_IMAGE_TYPE_NOT_SUPPORTED' => 'Unable to determine the type of captcha file, accepted only a JPG, GIF, PNG',
        'ERROR_KEY_DOES_NOT_EXIST' => 'We used a non-existent key',
        'ERROR_WRONG_USER_KEY' => 'Invalid format setting key, must be 32 characters',
        'ERROR_WRONG_ID_FORMAT' => 'Invalid format ID captcha. ID must contain only digits',
        'ERROR_WRONG_FILE_EXTENSION' => 'Your captcha is incorrect extension, allowed extensions jpg, jpeg, gif, png',
    );

    public function __construct($apiKey, $minLength = 0, $maxLength = 3) {
        if (empty($apiKey)) {
            throw new Exception('Api key apmty');
        } else {
            $this->apiKey = $apiKey;
            $this->minLen = $minLength;
            $this->maxLen = $maxLength;
        }
    }

    public function decode($filename) {
        if (!file_exists($filename)) {
            die('Captcha file not exist');
        }
        try {
            $postData = array(
                'method' => 'post',
                'key' => $this->apiKey,
                'file' => '@' . $filename,
                'phrase' => $this->isPhrase,
                'regsense' => $this->isRegSense,
                'numeric' => $this->isNumeric,
                'min_len' => $this->minLen,
                'max_len' => $this->maxLen,
                'language' => $this->language,
                'soft_id' => 2040009
            );

            $result = $this->excutePost(self::API_IN_URL, $postData);
            $this->setError($result);
            $this->captcha_id = end(explode("|", $result));
            return $this->getText();
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    protected function excutePost($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if (version_compare(PHP_VERSION, '5.5.0') >= 0 && version_compare(PHP_VERSION, '7.0') < 0) {
            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception("CURL error: " . curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }

    protected function getText() {
        $waitTime = 0;
        sleep($this->requestTimeout);
        while (true) {
            $result = file_get_contents(self::API_RES_URL . "?key={$this->apiKey}&action=get&id={$this->captcha_id}");
            $this->setError($result);
            if ($result == "CAPCHA_NOT_READY") {
                sleep($this->requestTimeout);
            } else {
                return $this->checkText($result);
            }
            $waitTime += $this->requestTimeout;
            if ($waitTime > $this->maxTimeout) {
                return false;
            }
        }
    }

    protected function checkText($result) {
        $ex = explode('|', $result);
        if (trim($ex[0]) == 'OK') {
            $this->result = trim($ex[1]);
            return true;
        }
        return false;
    }

    public function inccorect() {
        file_get_contents(self::API_RES_URL . "?key={$this->apiKey}&action=reportbad&id={$this->captcha_id}");
    }

    public function error() {
        return $this->error;
    }

    public function result() {
        return $this->result;
    }

    private function setError($error) {
        if (strpos($error, 'ERROR') !== false) {
            if (array_key_exists($error, $this->errors)) {
                throw new Exception($this->errors[$error]);
            } else {
                throw new Exception($error);
            }
        }
    }

}
