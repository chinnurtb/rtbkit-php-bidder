<?php

/* 
* @Author: root
* @Date:   2014-03-03 16:29:14
* @Last Modified by:   root
* @Last Modified time: 2014-03-06 12:13:04
*/

require('include.php');
require('lwrtb.php');

class MyBRcb extends BidRequestCb {

    public $userId=array();

    function call($res1, $res2) {
        $bidder = new Bidder($res1);
        $bidRequestEvent = new BidRequestEvent($res2);
        //processBid($bidRequestEvent, $bidder); 
        $tmp = json_decode($bidRequestEvent->bids, true);
        $bidResponse = array(
            "bids" => array(
                array(
                    "price" => '10USD/1M',
                    "creative" => 2
                    ),
                array(
                    "price" => '10USD/1M',
                    "creative" => 1
                    )
                )
            ); 
        $bidRequestEvent->bids = json_encode($bidResponse);
        $bidder->doBid($bidRequestEvent->id, $bidRequestEvent->bids);
    }
}

/**
 * Bidding Logic
 * @param  [type] $bidRequestEvent [description]
 * @param  [type] $bidder [description]
 * @return [type] $res -> 0 if ok, otherwise return error code [description]
 */
function processBid($bidRequestEvent, $bidder)
{
    /*
    DEBUG
    //print_r("Bid id : " . $bidRequestEvent->id . "\n");
    //print_r("Bid request : " . $bidRequestEvent->bidRequest . "\n");
    //print_r("Bids : " . $bidRequestEvent->bids . "\n");

    //var_dump($tmp);
    //{\"format\":\"728x90\",\"id\":2,\"name\":\"LeaderBoard\"}
     */
    
    // Recupere le/les usersId
    $tmpRequest = json_decode($bidRequestEvent->bidRequest, true);
    $this->userId = $tmpRequest["userIds"]["prov"];
    $this->userId = $tmpRequest["userIds"]["xchg"];

    $tmp = json_decode($bidRequestEvent->bids, true);

    // Recupere les keywords matchant l'IDV
    $keyword = keywordstore_get($this->userId["prov"]);
    // Récupère les id des annonces matchant ces keywords
    $ads_id = sphinx_query($keywords, $count);
    // Récupère les annonces à partir de MySQL
    $response = persist_retrieve_ads($ads_id);

    /*
    bid logic
     */
    // Marque les annonces comme étant affichées
    persist_incr_display($response);
    // Renvoie les annonces dans Redis
    adstoreSet($response);
    // Renvoie le Bid au router
    $bidRequestEvent->bids = json_encode($named_array);
    $bidder->doBid($bidRequestEvent->id, $bidRequestEvent->bids);
    return $res;
}

/**
 * Build bid from ads returned by MySql
 * @param  [type] $ads [description]
 * @return [type]      [description]
 */
function buildBidFromAds($ads)
{
    $bidResponse = array(
        "bids" => array(
            array(
                "price" => '10USD/1M',
                "creative" => 2
                ),
            array(
                "price" => '10USD/1M',
                "creative" => 1
                )
            )
        );
    return $bidResponse;
}

/**
 * Set Ads in Redis for a specified IDV
 * @param  [type] $idv [description]
 * @param  [type] $ads [description]
 * @return [type]      [description]
 */
function adstoreSet($idv, $ads)
{
    $adsTimestamp = array_fill_keys((array)$ads, $_SERVER['REQUEST_TIME']);
    
    // Redis
    $redis = new Redis();
    $redis->pconnect(REDIS_HOST, ADSSTORE_REDIS_PORT, REDIS_TIMEOUT);
    $redis->select(ADSSTORE_REDIS_TABLE_STORE);
    try {
        $redis->hMset($idv, $adsTimestamp);
    } catch (RedisException $exception) {
        metrics_incr('p.error.' . get_class($exception) . '.' . basename($exception->getFile(), '.php') . '_' . $exception->getLine());
    }
    $redis->close();
}

function findminCpc($ads)
{
    
}


/**
 * Simple extension for lwrtb.php
 */
class MyERcb extends ErrorCb {
    function call($res1, $res2)
    {
        print_r("ERROR_CALLBACK");
        $bidder = new Bidder($res1);
        $errorEvent = new ErrorEvent($res2);
        print_r($errorEvent->description);
    }
}

class MyDVcb extends DeliveryCb {
    function call($res1, $res2)
    {
        print_r("DELIVERY CALLBACK !");
        $bidder = new Bidder($res1);
        $dlvrEvent = new DeliveryEvent($res2);
        print_r($dlvrEvent);
        print_r($dlvrEvent->timestamp);
        print_r($dlvrEvent->id);
        print_r($dlvrEvent->bidRequest);
        print_r($dlvrEvent->bids);
        print_r($dlvrEvent->timeLeftMs);
        print_r($dlvrEvent->augmentations);
        print_r($dlvrEvent->winCostModel);
    }
}

class MyBREcb extends BidResultCb {
    function call($res1, $res2)
    {
                //print_r("RESULT_CALLBACK --------\n");
           /*     $bidder = new Bidder($res1);
                //print_r("SET BIDDER !");
                $brEvent = new BidResultEvent($res2);
                //print_r($brEvent);
                
                print_r("Result : " . $brEvent->result . "\n");
                print_r("Auction ID : " . $brEvent->auctionId . "\n");
                print_r("Bid request " . $brEvent->bidRequest . "\n");*/
            }
        }


/**
 * [keywordstore_get description]
 * @param  [type] $idv [description]
 * @return [type]      [description]
 */
function keywordstore_get($idv)
{
    // Redis
    $redis = new Redis();
    $redis->pconnect(REDIS_HOST, KEYWORDSTORE_REDIS_PORT, REDIS_TIMEOUT);
    $redis->select(KEYWORDSTORE_REDIS_TABLE_STORE);
    var_dump("CONNEXION TO REDIS OK");
    try {
        $keywords = $redis->hGetAll($idv);
    } catch (RedisException $exception) {
        metrics_incr('p.error.' . get_class($exception) . '.' . basename($exception->getFile(), '.php') . '_' . $exception->getLine());
    }
    $redis->close();
    
    return $keywords;
}

/**
 * [testSimpleBid description]
 * @return [type]
 */
function testSimpleBid()
{
    $agent_config = "{\"lossFormat\":\"lightweight\",
    \"winFormat\":\"full\",
    \"test\":false,
    \"minTimeAvailableMs\":5,
    \"account\":[\"hello\",\"world\"],
    \"bidProbability\":0.5,
    \"creatives\":[{\"format\":\"728x90\",\"id\":2,\"name\":\"LeaderBoard\",\"tagId\":2},
    {\"format\":\"160x600\",\"id\":0,\"name\":\"LeaderBoard\",\"tagId\":0},
    {\"format\":\"160x600\",\"id\":1,\"name\":\"BigBox\",\"tagId\":1}],
    \"errorFormat\":\"lightweight\",\"externalId\":0}";

    $proxy_config = "{\"installation\":\"rtb-test\",
    \"location\":\"mtl\",
    \"zookeeper-uri\":\"localhost:2181\",
    \"portRanges\":{\"logs\":[16000,17000],
    \"router\":[17000,18000],
    \"augmentors\":[18000,19000],
    \"configuration\":[19000,20000],
    \"postAuctionLoop\":[20000,21000],
    \"postAuctionLoopAgents\":[21000,22000],
    \"banker.zmq\":[22000,23000],
    \"banker.http\":9985,
    \"agentConfiguration.zmq\":[23000,24000],
    \"agentConfiguration.http\":9986,
    \"monitor.zmq\":[24000,25000],
    \"monitor.http\":9987,
    \"adServer.logger\":[25000,26000]}}";

    $bob = new Bidder("bob", $proxy_config);

    $br_cb = new MyBRcb();
    $dv_cb = new MyDVcb();
    $er_cb = new MyERcb();
        //$res_cb = new MyBREcb();
    $res_cb = new BidResultCb();
    $bob->setBidRequestCb ($br_cb);
    $bob->setDeliveryCb ($dv_cb);
    $bob->setErrorCb ($er_cb);
    $bob->setBidResultCb ($res_cb);

    $bob->init();
    $bob->doConfig($agent_config);
    $bob->start(true);
}


testSimpleBid();

//testComplexBid();

?>
