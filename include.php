<?php
/* 
* @Author: gregory.barry
* @Date:   2014-03-05 14:49:28
* @Last Modified by:   root
* @Last Modified time: 2014-03-06 12:13:49
*/

//include 'getConfig.php';

$config = "./php_agent_config.php";

function 

$proxy_config = getProxyFromConfig($config);

$agent_config = getAgentFromCOnfig($config);

// -------- REDIS GLOBAL CONF -----
const REDIS_TIMEOUT                                 = 1;
const REDIS_HOST                                    = '5.39.12.11'; // red0.pus2011.com

// -------- ADSSTORE --------------
const ADSSTORE_REDIS_PORT                       = 6377;
const ADSSTORE_REDIS_TABLE_STORE                = 0;

// -------- KEYWORDSTORE ----------

// // Paramètres de la base Redis relative au keywordStore
const KEYWORDSTORE_REDIS_PORT                       = 6377;
const KEYWORDSTORE_REDIS_TABLE_STORE                = 0;

?>
