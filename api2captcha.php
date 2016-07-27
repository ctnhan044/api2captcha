<?php

class Api2Captcha {

    public $requestTimeout = 5;
    public $maxTimeout = 60;
    public $isPhrase = 0;
    public $isRegSense = 0;
    public $isNumeric = 0;
    public $minLen = 0;
    public $maxLen = 0;
    public $language = 0;
    private $domain = 'http://rucaptcha.com';
    protected $apiKey;
    protected $result;
    protected $error;
    private $errors = array(
        'ERROR_NO_SLOT_AVAILABLE' => 'Нет свободных работников в данный момент, попробуйте позже либо повысьте свою максимальную ставку здесь',
        'ERROR_ZERO_CAPTCHA_FILESIZE' => 'Размер капчи которую вы загружаете менее 100 байт',
        'ERROR_TOO_BIG_CAPTCHA_FILESIZE' => 'Ваша капча имеет размер более 100 килобайт',
        'ERROR_ZERO_BALANCE' => 'Нулевой либо отрицательный баланс',
        'ERROR_IP_NOT_ALLOWED' => 'Запрос с этого IP адреса с текущим ключом отклонен. Пожалуйста смотрите раздел управления доступом по IP',
        'ERROR_CAPTCHA_UNSOLVABLE' => 'Не смог разгадать капчу',
        'ERROR_BAD_DUPLICATES' => 'Функция 100% распознавания не сработала и-за лимита попыток',
        'ERROR_NO_SUCH_METHOD' => 'Вы должны слать параметр method в вашем запросе к API, изучите документацию',
        'ERROR_IMAGE_TYPE_NOT_SUPPORTED' => 'Невозможно определить тип файла капчи, принимаются только форматы JPG, GIF, PNG',
        'ERROR_KEY_DOES_NOT_EXIST' => 'Использован несуществующий key',
        'ERROR_WRONG_USER_KEY' => 'Неверный формат параметра key, должно быть 32 символа',
        'ERROR_WRONG_ID_FORMAT' => 'Неверный формат ID каптчи. ID должен содержать только цифры',
        'ERROR_WRONG_FILE_EXTENSION' => 'Ваша каптча имеет неверное расширение, допустимые расширения jpg,jpeg,gif,png',
    );
    private $captcha_id;

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
        try {
            $postData = array('method' => 'post',
                'key' => $this->apiKey,
                'file' => '@' . $filename,
                'phrase' => $this->isPhrase,
                'regsense' => $this->isRegSense,
                'numeric' => $this->isNumeric,
                'min_len' => $this->minLen,
                'max_len' => $this->maxLen,
                'language' => $this->language,
                'soft_id' => 882
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "{$this->domain}/in.php");
            if (version_compare(PHP_VERSION, '5.5.0') >= 0 && version_compare(PHP_VERSION, '7.0') < 0) {
                curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new Exception("CURL error: " . curl_error($ch));
            }
            curl_close($ch);
            $this->setError($result);
            list(, $this->captcha_id) = explode("|", $result);
            $waitTime = 0;
            sleep($this->requestTimeout);

            while (true) {
                $result = file_get_contents("{$this->domain}/res.php?key={$this->apiKey}&action=get&id={$this->captcha_id}");
                $this->setError($result);
                if ($result == "CAPCHA_NOT_READY") {
                    $waitTime += $this->requestTimeout;
                    if ($waitTime > $this->maxTimeout) {
                        break;
                    }
                    sleep($this->requestTimeout);
                } else {
                    $ex = explode('|', $result);
                    if (trim($ex[0]) == 'OK') {
                        $this->result = trim($ex[1]);
                        return true;
                    }
                }
            }
            throw new Exception('Timeout');
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function inccorect() {
        file_get_contents("{$this->domain}/res.php?key={$this->apiKey}&action=reportbad&id={$this->captcha_id}");
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
?>
