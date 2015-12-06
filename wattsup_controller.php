<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

function wattsup_controller()
{
    global $mysqli, $redis, $user, $session, $route, $feed_settings;

    // There are no actions in the input module that can be performed with less than write privileges
    if (!$session['write']) return array('content'=>false);

    $result = false;

    // Need to get correct files so that we can make inputs 
    require_once "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli, $redis, $feed_settings);

    require_once "Modules/input/input_model.php";
    $input = new Input($mysqli, $redis, $feed);



    $result = 'ok';


    if ($route->format == 'text') {
        $result = 'text!';
    }



    
    // include "Modules/app/AppConfig_model.php";
    // $appconfig = new AppConfig($mysqli);

    // if ($route->format == 'html')
    // {
    //     if ($route->action == "" && $session['write']) {
    //         $result = view("Modules/app/client.php",array());
    //     }
    // }
    
    // if ($route->format == 'json')
    // {
    //     if ($route->action == "setconfig" && $session['write']) 
    //         $result = $appconfig->set($session['userid'],get('data'));
            
    //     if ($route->action == "getconfig" && $session['read']) 
    //         $result = $appconfig->get($session['userid']);
        
    //     if ($route->action == "dataremote")
    //     {
    //         $id = (int) get("id");
    //         $start = (float) get("start");
    //         $end = (float) get("end");
    //         $interval = (int) get("interval");
            
    //         $result = json_decode(file_get_contents("http://emoncms.org/feed/data.json?id=$id&start=$start&end=$end&interval=$interval&skipmissing=0&limitinterval=0"));
    //     }
        
    //     if ($route->action == "valueremote")
    //     {
    //         $id = (int) get("id");
    //         $result = (float) json_decode(file_get_contents("http://emoncms.org/feed/value.json?id=$id"));
    //     }
    // }

    return array('content'=>$result);
}
