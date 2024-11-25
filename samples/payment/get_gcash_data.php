<?php


/**
 * GCASH Data
 *
 * @package Capitalist API sample
 *
 * For questions, help, comments, discussion, etc., please
 * send e-mail to support@capitalist.net
 *
 * @link https://www.capitalist.net
 * @copyright 2024 Capitalist
 * @version 1.0
 *
 * Документация
 * @see https://capitalist.net/developers/api
 *
 */


include_once(__DIR__ . '/../config/config.php');

/**
 * Детальное описание методов см. в Client.php
 *
 * метод getSecurityAttributes - операция API get_token
 * метод pushBatchAdvanced - операция API import_batch_advanced
 */
$client = new \capitalist\api\Client($API_url);

$client->startSession($username, $password, true);

$gcashData = $client->getGCashData();

echo "\n\nGCASH data: \n". json_encode($gcashData);

