<?php

/*
 * Simple config file
 */
require('sailthru/Sailthru_Client.php');
require('sailthru/Sailthru_Client_Exception.php');
require('sailthru/Sailthru_Util.php');
require('Sailthru_Response.php');

$api_key = 'SAILTHRU-API-KEY';
$api_secret = 'SAILTHRU-API-SECRET';

$sailthru = new Sailthru_Client($api_key, $api_secret);

