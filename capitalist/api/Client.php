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

    const HEADER_X_RESPONSE_FORMAT = 'x-response-format';

    const FORMAT_JSON = 'json'; // рекомендуемый

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

    protected $_API_url = null;

    /** @var string */
    protected $username;

    /** @var string */
    protected $password;

    /** @var bool */
    protected $plainPassword = false;

    /** @var string */
    protected $passwordKey = 'encrypted_password';

    /** @var string */
    protected $token;
    /** @var int */
    protected $lastErrorCode;
    /** @var string */
    protected $lastErrorMessage;
    /** @var string */
    protected $lastResult;

    /** @var string */
    protected $apiAuthUser = '';
    /** @var string */
    protected $apiAuthPassword = '';

    /**
     * Формат ответов - csv, json, json-lite
     *
     * @var string|null
     */
    protected $responseFormat = self::FORMAT_JSON;

    /**
     * HTTP Заголовки крайнего ответа
     *
     * @var array
     */
    protected $lastResponseHeaders = [];

    public $debugLog = false;

    function __construct(string $APIurl)
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

    public function str2hex($str)
    {
        $unpacked = unpack('H*', $str);
        return array_shift($unpacked);
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
            default:
            case self::FORMAT_JSON:
                $response = $this->getJsonResult();
                $result = $response['data'];
                break;
        }

        $this->token = $result['token'];

        return $result;
    }


    /**
     * @return string
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return $this
     */
    public function setUsername($username): self
    {
        $this->username = $username;
        return $this;
    }

    public function hasError(): bool
    {
        return $this->getLastErrorCode() != 0;
    }

    /**
     * @return int
     */
    public function getLastErrorCode(): ?int
    {
        return ((int)$this->lastErrorCode) ?: 0;
    }

    /**
     * @return string
     */
    public function getLastErrorMessage(): ?string
    {
        return $this->lastErrorMessage;
    }

    /**
     * @param string $lastErrorMessage
     * @return $this
     */
    public function setLastErrorMessage(string $lastErrorMessage): self
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
    public function getJsonResult(): ?array
    {
        return json_decode($this->lastResult, true);
    }

    /**
     * Строку ответа getLastResult превращает в массив в зависимости от формата ответа
     *
     * @return array
     * @throws \Exception
     */
    public function getLastResultAsArray(): array
    {
        switch ($this->getLastResponseFormat()) {
            default:
            case self::FORMAT_JSON:
                return $this->getJsonResult();
        }
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
    public function setLastErrorCode(int $lastErrorCode): self
    {
        $this->lastErrorCode = $lastErrorCode;
        return $this;
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
    public function pushBatchAdvanced($batchContent, $accountRUR, $accountEUR, $accountUSD, $accountBTC = null, string $verificationType = self::PIN_PASSWORD, string $verificationData = null)
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
     * @param string $password
     * @return $this
     */
    public function setPassword($password): self
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Операция API: documents_search
     *
     * @param null|string $customNumber
     * @param null|string $beginDate
     * @param null|string $endDate
     * @return array|null
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
     * Операция API: get_documents_history_ext
     *
     * @param string $account
     * @param string|null $from (optional)
     * @param string|null $to (optional)
     * @param string|null $docState (optional)
     * @param int|null $limit (optional)
     * @param int|null $page (optional)
     * @return string
     *
     * @throws \Exception
     */
    public function getDocumentsHistory(
        $periodBegin = null, $periodEnd = null,
        $docState = null, $limit = 30, $page = 1,
        $account = null, $searchRequisites = null)
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
     * @return array|null
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
     * Проверка, верифицирован ли владелец счета
     *
     * Операция API: is_verified_account
     *
     * @param string $account Номер счета, например, R0978541
     * @return bool
     * @throws \Exception
     */
    public function isVerifiedUserByAccountNumber($account): bool
    {
        if (!$this->sendPost($this::OPERATION_IS_VERIFIED_ACCOUNT, array(
            'account' => $account,
            $this->passwordKey => $this->password,
            'token' => $this->token
        )))
            throw new \Exception(sprintf('Error: %s: %s', $this->getLastErrorCode(), $this->getLastErrorMessage()));

        return (bool)$this->getLastResult();
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

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            self::HEADER_X_RESPONSE_FORMAT . ': ' . ($this->getResponseFormat() ?: self::FORMAT_JSON),
        ));

        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $this->setLastResponseHeaders($this->parseHeaders($response, $header_size));
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
            case self::FORMAT_JSON:
                return $this->validateJsonResult($result);
        }
    }

    protected function validateJsonResult($result)
    {
        try {
            $array = json_decode($result, true);
        } catch (\Exception $e) {
            throw new \Exception('Invalid response.');
        }
        if (!$array || !isset($array['code']) || !isset($array['message']) || !isset($array['data']))
            throw new \Exception('Invalid response.');
        $this->setLastErrorCode((int)$array['code']);
        $this->setLastErrorMessage((string)$array['message']);
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
        if (!preg_match('/^\d+;.+$/', $lines[0]))
            throw new \Exception('Invalid response.');
        $firstline = explode(';', $lines[0]);
        $errorCode = $firstline[0];
        $this->setLastErrorCode((int)$errorCode);
        if (!$errorCode) $this->setLastErrorMessage(false);
        else $this->setLastErrorMessage($firstline[1] ?? '');
        return !$this->hasError();
    }

    /**
     * @return string
     */
    public function getResponseFormat(): ?string
    {
        return $this->responseFormat;
    }

    /**
     * @param string $responseFormat
     * @return $this
     */
    public function setResponseFormat(string $responseFormat): self
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
    public function getLastResponseFormat(): ?string
    {
        $headers = $this->getLastResponseHeaders();
        return $headers[self::HEADER_X_RESPONSE_FORMAT] ?? null;
    }


    /**
     * @return array
     */
    public function getLastResponseHeaders(): ?array
    {
        return $this->lastResponseHeaders;
    }

    /**
     * @param array $lastResponseHeaders
     * @return $this
     */
    public function setLastResponseHeaders(array $lastResponseHeaders): self
    {
        $this->lastResponseHeaders = $lastResponseHeaders;
        return $this;
    }

    /**
     * @param $response
     * @param $header_size
     * @return array
     */
    public function parseHeaders($response, $header_size): array
    {
        // Extract the headers portion from the response
        $headers_string = substr($response, 0, $header_size);

        // Initialize the headers array
        $headers = [];

        // Split the headers string into an array of lines
        $header_lines = preg_split('/\r\n|\n|\r/', $headers_string);

        // Process each header line
        foreach ($header_lines as $header_line) {
            // Skip empty lines and the HTTP status line
            if (empty($header_line) || strpos($header_line, 'HTTP/') === 0) {
                continue;
            }

            // Split each line into name and value
            list($name, $value) = explode(':', $header_line, 2);

            // Trim whitespace and convert header name to lowercase
            $name = strtolower(trim($name));
            $value = trim($value);

            // Add to the headers array
            $headers[$name] = $value;
        }

        return $headers;
    }

    public function log($string): void
    {
        if ($this->debugLog)
            printf("\n[%s] debug log: %s\n", date('d.m.Y H:i:s'), $string);
    }
}
