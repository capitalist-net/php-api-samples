<?php

include_once(__DIR__ . "/../vendor/autoload.php");

/**
 * Файл с параметрами 
 *
 * Project:     Capitalist: API sample
 * File:        config.php
 *
 * For questions, help, comments, discussion, etc., please
 * send e-mail to support@capitalist.net
 *
 * @link https://www.capitalist.net
 * @copyright 2019 Capitalist
 * @version 1.1
 */
 
$username = 			'myusername';		// Логин
$password = 			'mypassword';		// Пароль для входа в систему


/*********************************** *************************************************
 *                              ВНИМАНИЕ!                                           *
 * В целях безопасности рекомендуем хранить файл ключа на съемном носителе          *
 ************************************************************************************/

$privateKey = 			__DIR__ . '/my-certificate.pem';	// Путь к файлу приватного ключа (*.pem), полученного пользователем при подключении сертификата
$privateKeyPassword = 	null; 		// Пароль к файлу с ключем, заданный пользователем при подключении сертификата (если не установлен - присвоить null)

$samplePin = '111111';
$sampleSmsCode = '123456';

/**
 * Счета для списания при загрузке платежей
 */
$accountRUR = 'R0978541';  // Номер вашего счета для платежей в валюте RUR
$accountEUR = 'E0978111';  // Номер вашего счета для платежей в валюте EUR
$accountUSD = 'U0978719';  // Номер вашего счета для платежей в валюте USD
$accountBTC = 'B0978166';  // Номер вашего счета для платежей в валюте BTC


// ID батча
$sampleBatchId = '7a157e3d-cb28-4565-9ba9-218a606810b3';


/**
 * Адрес API сервиса
 */
$API_url = 'https://api.capitalist.net/';

header('Content-Type: text/plain; charset=utf-8');