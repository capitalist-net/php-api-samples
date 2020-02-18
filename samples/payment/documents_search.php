<?php

/**
 * Поиск документов, отправленных через API и массовые платежи
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

$client = new \capitalist\api\Client($API_url);

$client->startSession($username, $password);

$documents = $client->documentsSearch(null/*, '03.07.2016', '04.07.2016'*/);

echo "\n\nDocumentsSearch: \n". $batchInfo;


