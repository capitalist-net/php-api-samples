<?php

/**
 * Получение курсов обмена Capitalist
 *
 * @package Capitalist API sample
 *
 * For questions, help, comments, discussion, etc., please
 * send e-mail to support@capitalist.net
 *
 * @link https://www.capitalist.net
 * @copyright 2019 Capitalist
 * @version 0.7
 *
 * Документация
 * @see https://capitalist.net/developers/api
 *
 */
include_once(__DIR__ . '/../config/config.php');

/**
 * Смотрите Client.php для подробностей
 *
 * Метод getSecurityAttributes - операция API get_token
 */
$client = new \capitalist\api\Client($API_url);

$client->startSession($username, $password);

$results = $client->callOperation('currency_rates');

/**
 * Вывод результатов
 */
echo $results;




