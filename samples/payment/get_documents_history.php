<?php

/**
 * Получение истории операций
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
 */
$client = new \capitalist\api\Client($API_url);

$client->startSession($username, $password);


/**
 * Запрос истории операций
 *
 * Метод getDocumentsHistory - операция API get_documents_history_ext
 */
$results_history = $client->getDocumentsHistory(
    (new DateTime())->sub((new DateInterval('P30D')))->format('d.m.Y'),			// Начальная дата
    (new DateTime())->format('d.m.Y')			// Конечная дата
);



/**
 * Вывод результатов
 */
echo "\n\nGetDocumentsHistory: \n".$results_history;
