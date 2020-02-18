<?php

/**
 * Отправка батча с подписью PIN кодом и получение информации по обработке отправленных записей
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


// Записи батча для загрузки в систему
$batchRecords =  <<<'BATCH'
WM;R555511198765;51.21;RUR;1009;Payout PIN
CAPITALIST;R0100271;12.34;RUR;9666;Payout CAP
BATCH;


include_once(__DIR__ . '/../config/config.php');

/**
 * Детальное описание методов см. в Client.php
 *
 * метод getSecurityAttributes - операция API get_token
 * метод pushBatchAdvanced - операция API import_batch_advanced
 * метод getBatchRecords - операция API get_batch_info
 */
$client = new \capitalist\api\Client($API_url);

$client->startSession($username, $password, true);


/**
 * Отправка массового платежа в систему (операция import_batch)
 */
$pushBatchResult = $client->pushBatchAdvanced(
    $batchRecords,      //  Записи массового платежа
    $accountRUR,
    $accountEUR,
    $accountUSD,
    $accountBTC,
    \capitalist\api\VerificationTypesInterface::PIN_PASSWORD,
    $samplePin
);

echo "\n\nImportBatchAdvanced: \n".$pushBatchResult;

/**
 *  Получение подробной информации о статусе загруженных записей батча в постраничном разбиении (операция get_batch_info)
 *  Максимальный допустимый размер страницы - 1000.
 */

$pageSize = 100; // Количество записей батча, которые нужно получить, начиная с $offset (0 для первой записи)
$offset = 0; // Позиция, начиная с которой выводить набор записей

$pushBatchResultArray = $client->getLastResultAsArray();

$batchInfo = $client->getBatchRecords(
    $pushBatchResultArray['data']['id'],    // ID батча, полученный ранее при успешном вызове import_batch;
    $pageSize,              // Количество записей батча, которые нужно получить, начиная с $offset (0 для первой записи и т.д.)
    $offset                // Позиция, начиная с которой выводить набор записей (0 для первой записи)
);

echo "\n\nGetBatchInfo: \n".$batchInfo;




