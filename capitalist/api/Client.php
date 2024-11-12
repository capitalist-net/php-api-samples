<?php

namespace capitalist\api;

use phpseclib3\Crypt\RSA;

/**
 * Смотрите актуальную документацию с примерами по адресу:
 * Read actual documentation at
 *
 * https://capitalist.net/developers/api
 *
 *
 * Class Client
 * File:    Client.php
 *
 * For questions, help, comments, discussion, etc., please
 * send e-mail to support@capitalist.net
 *
 * @link https://www.capitalist.net
 * @copyright 2015 Capitalist
 * @version 0.7
 */
class Client
    implements VerificationTypesInterface, OperationsInterface
{

    const FORMAT_CSV = 'csv',                       // устаревший
        FORMAT_JSON = 'json',                    // рекомендуемый
        FORMAT_JSONLITE = 'json-lite';              // устаревший

    const EMAIL_CONFIRM_TYPE_ACTIVATION = 1,
        EMAIL_CONFIRM_TYPE_CHANGE = 0;

    const NOTIFICATION_CHANNEL_EMAIL = 'EMAIL',
        NOTIFICATION_CHANNEL_SMS = 'SMS';

    /**
     * @return string
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    const NOTIFICATION_LANG_RU = 'ru',
        NOTIFICATION_LANG_EN = 'en';

    private $_API_url = null;

    /** @var string */
    private $username;
    /** @var string */
    private $password;

    /** @var bool */
    protected $plainPassword = false;

    /** @var string */
    protected $passwordKey = 'encrypted_password';

    /** @var string */
    private $token;
    /** @var int */
    private $lastErrorCode;
    /** @var string */
    private $lastErrorMessage;
    /** @var string */
    private $lastResult;

    /** @var string */
    private $apiAuthUser = '';
    /** @var string */
    private $apiAuthPassword = '';

    /**
     * Формат ответов - csv, json, json-lite
     *
     * @var string
     */
    private $responseFormat = self::FORMAT_JSON;

    /**
     * HTTP Заголовки крайнего ответа
     *
     * @var array
     */
    protected $lastResponseHeaders = [];

    public $debugLog = false;

    function __construct($APIurl)
    {
        $this->_API_url = $APIurl;
        $p = parse_url($APIurl);
        if (isset($p['user'])) $this->apiAuthUser = $p['user'];
        if (isset($p['pass'])) $this->apiAuthPassword = $p['pass'];
    }

    /**
     * Инициализация сессии
     */
    public function startSession($username, $password, $plainPassword = false)
    {
        $this->setUsername($username);
        $this->plainPassword = (bool)$plainPassword;
        $this->passwordKey = $this->plainPassword ? 'plain_password' : 'encrypted_password';

        $this->setPassword(
            $this->plainPassword
                ? $password
                : $this->encryptPassword($this->getSecurityAttributes(), $password)
        );

        $this->log('Encrypted password: ' . $this->password);
    }

    public function str2hex( $str ) {
        $unpacked = unpack('H*', $str);
        return array_shift( $unpacked );
    }

    /**
     * Шифрование пароля
     */
    public function encryptPassword($attributes, $password): ?string
    {
        $key = RSA::loadPublicKey($attributes['rsa_public_key_pkcs1_pem'] ?? null);
        $key = $key->withPadding(RSA::ENCRYPTION_PKCS1);
        return $this->str2hex($key->encrypt($password));
    }

    /**
     * Получение атрибутов шифрования и сессионного ключа (токена)
     *
     * Операция API: get_token
     *
     * @return array
     * @throws \Exception
     */
    public function getSecurityAttributes(): array
    {
        $params = $this->plainPassword ? [$this->passwordKey => $this->password] : [];
        if (!$this->sendPost($this::OPERATION_GET_TOKEN, $params))
            throw new \Exception(sprintf('Error: %s: %s', $this->getLastErrorCode(), $this->getLastErrorMessage()));

        switch ($this->getLastResponseFormat()) {
            case self::FORMAT_JSON:
                $response = $this->getJsonResult();
                $result = $response['data'];
                break;
            case self::FORMAT_JSONLITE:
                $response = $this->getJsonResult();
                $result = array(
                    'token' => $response['data'][0][1],
                    'modulus' => $response['data'][0][2],
                    'exponent' => $response['data'][0][3],
                    'rsa_public_key_pkcs1_pem' => $response['data'][0][4] ?? null
                );
                break;
            default:
            case self::FORMAT_CSV:
                $response = $this->getCsvResult();
                $result = array(
                    'token' => $response[0][1],
                    'modulus' => $response[0][2],
                    'exponent' => $response[0][3],
                    'rsa_public_key_pkcs1_pem' => $response[0][4] ?? null
                );
                break;
        }

        $this->token = $result['token'];

        return $result;
    }

    /**
     * Отправка кода подтверждения для восстановления пароля
     *
     * Операция API: password_recovery_generate_code
     *
     * @param string $identity Имя пользователя или e-mail
     * @return bool
     * @throws \Exception
     */
    public function sendPasswordRecoveryCode($identity)
    {
        if (!$this->sendPost($this::OPERATION_PASSWORD_RECOVERY_GENERATE_CODE, array(
            'identity' => $identity
        ), true))
            throw new \Exception(sprintf('Error: %s: %s', $this->getLastErrorCode(), $this->getLastErrorMessage()));

        return $this->getLastResult();
    }


    /**
     * Отправка кода подтверждения для смены имейла или активации аккаунта
     *
     * Операция API: profile_get_verification_code
     *
     * @param string $login Имя пользователя
     * @param int $regCodeType Тип кода верификации
     * @return bool
     * @throws \Exception
     */
    public function sendEmailConfirmationCode($login, $regCodeType = self::EMAIL_CONFIRM_TYPE_ACTIVATION)
    {
        if (!$this->sendPost($this::OPERATION_GET_EMAIL_VERIFICATION_CODE, array(
            'login' => $login,
            'reg_code' => $regCodeType
        ), true))
            throw new \Exception(sprintf('Error: %s: %s', $this->getLastErrorCode(), $this->getLastErrorMessage()));

        return $this->getLastResult();
    }


    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    public function hasError()
    {
        return $this->getLastErrorCode() != 0;
    }

    /**
     * @return int
     */
    public function getLastErrorCode()
    {
        return $this->lastErrorCode;
    }

    /**
     * @return string
     */
    public function getLastErrorMessage()
    {
        return $this->lastErrorMessage;
    }

    /**
     * @param string $lastErrorMessage
     * @return $this
     */
    public function setLastErrorMessage($lastErrorMessage)
    {
        $this->lastErrorMessage = $lastErrorMessage;
        return $this;
    }

    /**
     * @return string|array|mixed
     */
    public function getLastResult()
    {
        return $this->lastResult;
    }

    /**
     * @return mixed
     */
    public function getJsonResult()
    {
        return json_decode($this->lastResult, true);
    }

    /**
     * Строку ответа getLastResult превращает в массив в зависимости от формата ответа
     *
     * @return array
     * @throws \Exception
     */
    public function getLastResultAsArray()
    {
        switch ($this->getLastResponseFormat()) {
            case self::FORMAT_CSV:
                return $this->getCsvResult();
                break;
            case self::FORMAT_JSON:
                return $this->getJsonResult();
                break;
            case self::FORMAT_JSONLITE:
                return $this->getJsonliteResult();
                break;
            default:
                throw new \Exception('Unknown response format.');
                break;
        }
    }


    /**
     * @return array
     */
    public function getCsvResult()
    {
        $array = [];
        foreach ((array)explode("\n", $this->lastResult) as $line)
            $array[] = explode(';', $line);
        return $array;
    }

    /**
     * @return mixed
     */
    public function getJsonliteResult()
    {
        return json_decode($this->lastResult, true);
    }

    /**
     * @param string $lastResult
     * @return $this
     */
    public function setLastResult($lastResult)
    {
        $this->lastResult = $lastResult;
        return $this;
    }

    /**
     * @param int $lastErrorCode
     * @return $this
     */
    public function setLastErrorCode($lastErrorCode)
    {
        $this->lastErrorCode = $lastErrorCode;
        return $this;
    }

    /**
     * Операция API: import_batch
     *
     * @param string $batchContent
     * @param string $signature
     * @param string $accountRUR
     * @param string $accountEUR
     * @param string $accountUSD
     * @return array
     * @throws \Exception
     *
     * @deprecated
     */
    public function pushBatch($batchContent, $signature, $accountRUR, $accountEUR, $accountUSD)
    {
        throw new \Exception('API method import_batch is deprecated. Please use import_batch_advanced instead.');
    }

    /**
     * Операция API: import_batch_advanced
     *
     * @param string $batchContent
     * @param string $accountRUR
     * @param string $accountEUR
     * @param string $accountUSD
     * @param string $verificationType
     * @param string $verificationData
     * @return array
     * @throws \Exception
     */
    public function pushBatchAdvanced($batchContent, $accountRUR, $accountEUR, $accountUSD, $accountBTC = null, $verificationType = SELF::PIN_PASSWORD, $verificationData = null)
    {
        if ($verificationType == self::PIN_PASSWORD)
            $verificationData = md5($verificationData);

        if (!$this->sendPost($this::OPERATION_IMPORT_BATCH_ADV, array(
            $this->passwordKey => $this->password,
            'token' => $this->token,
            'account_RUR' => $accountRUR,
            'account_EUR' => $accountEUR,
            'account_USD' => $accountUSD,
            'account_BTC' => $accountBTC,
            'batch' => $batchContent,
            'verification_type' => $verificationType,
            'verification_data' => $verificationData
        ))
        )
            throw new \Exception(sprintf('Error: %s: %s', $this->getLastErrorCode(), $this->getLastErrorMessage()));

        return $this->getLastResult();
    }

    /**
     * Операция API: add_payment_notification
     *
     * @return array
     * @throws \Exception
     */
    public function addPaymentNotification($document, $channel, $address, $language = self::NOTIFICATION_LANG_RU): array
    {
        if (!$this->sendPost($this::OPERATION_ADD_NOTIFICATION, array(
            'encrypted_password' => $this->getPassword(),
            $this->passwordKey => $this->password,
            'token' => $this->token,
            'document' => $document,
            'channel' => $channel,
            'address' => $address,
            'language' => $language
        ))
        )
            throw new \Exception(sprintf('Error: %s: %s', $this->getLastErrorCode(), $this->getLastErrorMessage()));

        return $this->getLastResult();
    }

    /**
     * Операция API: process_batch
     *
     * @param string $batchId
     * @param string $verificationType
     * @param string $verificationData
     * @return array
     * @throws \Exception
     */
    public function processBatch($batchId, $verificationType, $verificationData)
    {
        if (!$this->sendPost($this::OPERATION_PROCESS_BATCH, array(
            $this->passwordKey => $this->password,
            'token' => $this->token,
            'batch_id' => $batchId,
            'verification_type' => $verificationType,
            'verification_data' => $verificationData
        ))
        )
            throw new \Exception(sprintf('Error: %s: %s', $this->getLastErrorCode(), $this->getLastErrorMessage()));

        return $this->getLastResult();
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Операция API: documents_search
     *
     * @return array
     * @throws \Exception
     */
    public function documentsSearch($customNumber = null, $beginDate = null, $endDate = null)
    {
        if (!$this->sendPost($this::OPERATION_DOCUMENTS_SEARCH, array(
            $this->passwordKey => $this->password,
            'token' => $this->token,
            'customNumber' => $customNumber,
            'beginDate' => $beginDate,
            'endDate' => $endDate,
        ))
        )
            throw new \Exception(sprintf('Error: %s: %s', $this->getLastErrorCode(), $this->getLastErrorMessage()));

        return $this->getLastResult();
    }


    /**
     * Операция API: get_batch_info
     *
     * @param string $batchId
     * @param int $pageSize
     * @param int $offset
     * @return array
     * @throws \Exception
     */
    public function getBatchRecords($batchId, $pageSize = 100, $offset = 0)
    {
        if (!$this->sendPost($this::OPERATION_GET_BATCH_INFO, array(
            $this->passwordKey => $this->password,
            'token' => $this->token,
            'batch_id' => $batchId,
            'page_size' => $pageSize,
            'start_offset' => $offset
        ))
        )
            throw new \Exception(sprintf('Error: %s: %s', $this->getLastErrorCode(), $this->getLastErrorMessage()));

        return $this->getLastResult();
    }

    /**
     * Операция API: register_invitee
     *
     * @param string $username
     * @param string $email
     * @param string $nickname
     * @param bool|string $mobile (optional)
     * @return string
     * @throws \Exception
     */
    public function registerInvitee($username, $email, $nickname, $mobile = false)
    {
        if (!$this->sendPost($this::OPERATION_REGISTER_INVITEE, array(
            $this->passwordKey => $this->password,
            'token' => $this->token,
            'invitee_login' => $username,
            'invitee_email' => $email,
            'invitee_nickname' => $nickname,
            'invitee_mobile' => $mobile
        ))
        )
            throw new \Exception(sprintf('Error: %s: %s', $this->getLastErrorCode(), $this->getLastErrorMessage()));

        return $this->getLastResult();
    }

    /**
     * Операция API: get_documents_history_ext
     *
     * @param string $account
     * @param string $from (optional)
     * @param string $to (optional)
     * @param string $docState (optional)
     * @param int $limit (optional)
     * @param int $page (optional)
     * @return string
     *
     * @throws \Exception
     */
    public function getDocumentsHistory($periodBegin = null, $periodEnd = null, $docState = null, $limit = 30, $page = 1, $account = null, $searchRequisites = null)
    {
        if (!$this->sendPost($this::OPERATION_GET_HISTORY_EXT, array(
            $this->passwordKey => $this->password,
            'token' => $this->token,
            'account' => $account,
            'external_account' => $searchRequisites,
            'period_from' => $periodBegin,
            'period_to' => $periodEnd,
            'document_state' => $docState,
            'limit' => $limit,
            'page' => $page
        ))
        )
            throw new \Exception(sprintf('Error: %s: %s', $this->getLastErrorCode(), $this->getLastErrorMessage()));

        return $this->getLastResult();
    }

    /**
     * Операция API: get_documents_history (УСТАРЕВШЕЕ)
     *
     * @param string $account
     * @param string $from (optional)
     * @param string $to (optional)
     * @param string $docState (optional)
     * @param int $limit (optional)
     * @param int $page (optional)
     * @return string
     *
     * @throws \Exception
     * @see getDocumentsHistory()
     *
     * @deprecated
     */
    public function getHistory($account, $from = null, $to = null, $docState = null, $limit = 30, $page = 1)
    {
        throw new \Exception('Please use get_documents_history_ext instead.');

        if (!$this->sendPost($this::OPERATION_GET_HISTORY_DEPRECATED, array(
            $this->passwordKey => $this->password,
            'token' => $this->token,
            'account' => $account,
            'period_from' => $from,
            'period_to' => $to,
            'document_state' => $docState,
            'limit' => $limit,
            'page' => $page
        ))
        )
            throw new \Exception(sprintf('Error: %s: %s', $this->getLastErrorCode(), $this->getLastErrorMessage()));

        return $this->getLastResult();
    }

    /**
     * Операция API: get_documents_history_test
     *
     * @param string $account
     * @param string $from (optional)
     * @param string $to (optional)
     * @param string $docState (optional)
     * @param int $limit (optional)
     * @param int $page (optional)
     * @return string
     * @throws \Exception
     * @internal param string $token
     */
    public function getHistoryTest($account, $from = null, $to = null, $docState = null, $limit = 30, $page = 1)
    {
        if (!$this->sendPost($this::OPERATION_GET_HISTORY_TEST, array(
            $this->passwordKey => $this->password,
            'token' => $this->token,
            'account' => $account,
            'period_from' => $from,
            'period_to' => $to,
            'document_state' => $docState,
            'limit' => $limit,
            'page' => $page
        ))
        )
            throw new \Exception(sprintf('Error: %s: %s', $this->getLastErrorCode(), $this->getLastErrorMessage()));

        return $this->getLastResult();
    }

    /**
     * Операция API: registration_email_confirm
     *
     * @param string $codeFromEmail
     * @return bool
     * @throws \Exception
     */
    public function registrationEmailConfirm($codeFromEmail)
    {
        if (!$this->sendPost($this::OPERATION_REGISTRATION_EMAIL_CONFIRM, array(
            'code' => $codeFromEmail,
        ))
        )
            throw new \Exception(sprintf('Error: %s: %s', $this->getLastErrorCode(), $this->getLastErrorMessage()));

        return $this->getLastResult();
    }

    /**
     * Операция API: get_accounts
     *
     * @return string
     * @throws \Exception
     */
    public function getUserAccounts()
    {
        if (!$this->sendPost($this::OPERATION_GET_ACCOUNTS, array(
            $this->passwordKey => $this->password,
            'token' => $this->token
        ))
        )
            throw new \Exception(sprintf('Error: %s: %s', $this->getLastErrorCode(), $this->getLastErrorMessage()));

        return $this->getLastResult();
    }

    /**
     * Проверка, верифицрован ли владелец счета
     *
     * Операция API: is_verified_account
     *
     * @param string $account Номер счета, например, R0978541
     * @return bool
     * @throws \Exception
     */
    public function isVerifiedUserByAccountNumber($account)
    {
        if (!$this->sendPost($this::OPERATION_IS_VERIFIED_ACCOUNT, array(
            'account' => $account,
            $this->passwordKey => $this->password,
            'token' => $this->token
        )))
            throw new \Exception(sprintf('Error: %s: %s', $this->getLastErrorCode(), $this->getLastErrorMessage()));

        return $this->getLastResult();
    }

    /**
     * Вызов операции с произвольными параметрами
     *
     * @param $operation
     * @param array $params
     * @param bool $guest
     *
     * @return string
     * @throws \Exception
     */
    public function callOperation($operation, $params = [], $guest = false)
    {

        $p = array_merge($guest ? [] : [
            $this->passwordKey => $this->password,
            'token' => $this->token
        ], $params);

        if (!$this->sendPost($operation, $p))
            throw new \Exception(sprintf('Error: %s: %s', $this->getLastErrorCode(), $this->getLastErrorMessage()));

        return $this->getLastResult();
    }

    /**
     * Операция API: create_account
     *
     * @return string
     * @throws \Exception
     */
    public function createAccount($currency, $title)
    {
        if (!$this->sendPost($this::OPERATION_CREATE_ACCOUNT, array(
            $this->passwordKey => $this->password,
            'token' => $this->token,
            'account_name' => $title,
            'account_currency' => $currency
        ))
        )
            throw new \Exception(sprintf('Error: %s: %s', $this->getLastErrorCode(), $this->getLastErrorMessage()));

        return $this->getLastResult();
    }


    /**
     * Операция API: get_document_fee
     *
     * @param string $docType
     * @param array $paymentDetails
     * @return string
     * @throws \Exception
     */
    public function getDocumentFee($docType, $paymentDetails)
    {
        if (!$this->sendPost($this::OPERATION_GET_DOCUMENT_FEE, array_merge(array(
            $this->passwordKey => $this->password,
            'token' => $this->token,
            'document_type' => $docType,
        ), $paymentDetails))
        )
            throw new \Exception(sprintf('Error: %s: %s', $this->getLastErrorCode(), $this->getLastErrorMessage()));

        return $this->getLastResult();
    }

    /**
     * Service functions block
     */

    /**
     * Вызов API
     *
     * @param string $operation
     * @param array $params
     * @param bool $anonymous
     * @return mixed
     * @throws \Exception
     */
    protected function sendPost(string $operation, array $params = array(), bool $anonymous = false)
    {
        $data = array_merge(array('operation' => $operation), $params);

        if (!$anonymous)
            $data = array_merge($data, array('login' => $this->getUsername()));

        $ch = curl_init($this->_API_url);

        $options = array(
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER => true,     // return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING => "",       // handle all encodings
            CURLOPT_USERAGENT => "Client", // who am i
            CURLOPT_AUTOREFERER => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT => 120,      // timeout on response
            CURLOPT_MAXREDIRS => 10,       // stop after 10 redirects
            CURLOPT_SSL_VERIFYPEER => false     // Disabled SSL Cert checks
        );
        curl_setopt_array($ch, $options);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        if ($this->apiAuthUser)
            curl_setopt($ch, CURLOPT_USERPWD, $this->apiAuthUser . ($this->apiAuthPassword ? ':' . $this->apiAuthPassword : ''));

        if ($this->getResponseFormat()) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'x-response-format: ' . $this->getResponseFormat()
            ));
        }

        $response = curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $this->setLastResponseHeaders($this->parseHttpHeaders(substr($response, 0, $header_size)));
        $result = substr($response, $header_size);

        if (!$result)
            throw new \Exception('No result found');

        // $this->log('Response headers: '. implode("\n\r", $this->getLastResponseHeaders()));
        $this->log('Response body: ' . $result);

        return $this->setLastResult($result)->validateResult($result);
    }


    /**
     * Примитивная проверка, что ответ от сервера похож на заданный в заголовке.
     *
     * @param $result
     * @return bool
     * @throws \Exception
     */
    protected function validateResult($result)
    {
        switch ($this->getLastResponseFormat()) {
            default:
            case self::FORMAT_CSV:
                return $this->validateCsvResult($result);
                break;
            case self::FORMAT_JSON:
            case self::FORMAT_JSONLITE:
                return $this->validateJsonResult($result);
                break;
        }
    }

    protected function validateJsonResult($result)
    {
        try {
            $array = json_decode($result, true);
        } catch (Exception $e) {
            throw new \Exception('Invalid response.');
        }
        if (!$array || !isset($array['code']) || !isset($array['message']) || !isset($array['data']))
            throw new \Exception('Invalid response.');
        $this->setLastErrorCode($array['code']);
        $this->setLastErrorMessage($array['message']);
        return !$this->hasError();
    }

    /**
     * Примитивная проверка, что ответ от сервера похож на CSV и обработка кода ошибки API.
     *
     * @param $result
     * @return bool
     * @throws \Exception
     */
    protected function validateCsvResult($result)
    {
        $lines = explode("\n", $result);
        if (!preg_match('/^\d+\;.+$/', $lines[0]))
            throw new \Exception('Invalid response.');
        $firstline = explode(';', $lines[0]);
        $errorCode = $firstline[0];
        $this->setLastErrorCode($errorCode);
        if (!$errorCode) $this->setLastErrorMessage(false);
        else $this->setLastErrorMessage(isset($firstline[1]) ? $firstline[1] : '');
        return !$this->hasError();
    }

    /**
     * @return string
     */
    public function getResponseFormat()
    {
        return $this->responseFormat;
    }

    /**
     * @param string $responseFormat
     * @return $this
     */
    public function setResponseFormat($responseFormat)
    {
        $this->responseFormat = $responseFormat;
        return $this;
    }

    /**
     * Формат последнего ответа, пришедший в заголовке ответа,
     * при нормальном функционировании, всегда совпадае с x-response-format переданным в заголовках запроса.
     * Если не совпадают, значит что-то пошло не так.
     *
     * @return string
     */
    public function getLastResponseFormat()
    {
        $headers = $this->getLastResponseHeaders();
        return isset($headers['x-response-format']) ? $headers['x-response-format'] : null;
    }


    /**
     * @return array
     */
    public function getLastResponseHeaders()
    {
        return $this->lastResponseHeaders;
    }

    /**
     * @param array $lastResponseHeaders
     * @return $this
     */
    public function setLastResponseHeaders($lastResponseHeaders)
    {
        $this->lastResponseHeaders = $lastResponseHeaders;
        return $this;
    }

    /**
     * @param $string
     * @return array
     */
    public function parseHttpHeaders($string)
    {
        $headers = [];
        foreach ((array)explode("\r\n", $string) as $line) {
            $kv = explode(': ', $line, 2);

            if (isset($kv[0]) && trim($kv[0]))
                $headers[trim($kv[0])] = isset($kv[1]) ? $kv[1] : null;
        }
        return $headers;
    }

    public function log($string)
    {
        if ($this->debugLog)
            printf("\n[%s] debug log: %s\n", date('d.m.Y H:i:s'), $string);
    }
}
