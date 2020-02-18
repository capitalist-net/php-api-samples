<?php

/**
 *
 * Создание счета
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

include_once('../config/config.php');

/**
 * Смотрите Client.php для подробностей
 *
 * Метод getSecurityAttributes - операция API get_token
 */
$client = new \capitalist\api\Client($API_url);

$client->startSession($username, $password);

/**
 * Запрос перечня счетов (кошельков)
 *
 * Метод getUserAccounts - операция API get_accounts
 */

$results = $client->createAccount('RUR', 'Test API account');

/**
 * Вывод результатов
 */
echo "\n\nCreate Account: \n".$results;

/**
 * Запрос перечня счетов (кошельков)
 *
 * Метод getUserAccounts - операция API get_accounts
 */
$results = $client->getUserAccounts();

/**
 * Вывод результатов
 */
echo "\n\nGetUserAccounts: \n".$results;