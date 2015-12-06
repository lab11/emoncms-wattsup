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

    if ($route->action == 'post' && $route->format == 'text') {
        // This looks like a correctly configured Watts Up? .net POST

        $valid = true;
        $error = '';
        $userid = $session['userid'];
        $dbinputs = $input->get_inputs($userid);

        // id is set to the Watts Up? device ID
        $nodeid = preg_replace('/[^\p{N}\p{L}_\s-.]/u', '', get('id'));

        // Make sure we can do this. Copied from input_controller.php
        $validate_access = $input->validate_access($dbinputs, $nodeid);
        if (!$validate_access['success']) {
            $valid = false;
            $error = $validate_access['message'];
        } else {
            // Insert this record into the emoncms format
            $time = time();

            // Array to store the relevant fields in
            $data = array();

            $watts = get('w') / 10;
            $data['watts'] = $watts;

            // Iterate all new data items to insert
            $tmp = array();
            foreach ($data as $name => $value) {
                // Check if this is an existing field in this node or not
                if (!isset($dbinputs[$nodeid][$name])) {
                    // New field. 
                    $inputid = $input->create_input($userid, $nodeid, $name);
                    $dbinputs[$nodeid][$name] = true;
                    $dbinputs[$nodeid][$name] = array('id'=>$inputid, 'processList'=>'');
                    $input->set_timevalue($dbinputs[$nodeid][$name]['id'], $time, $value);
                } else {
                    // Existing field, just insert
                    $input->set_timevalue($dbinputs[$nodeid][$name]['id'], $time, $value);

                    // If there are processes listening to this field, we need
                    // to pass the data to those as well
                    if ($dbinputs[$nodeid][$name]['processList']) {
                        $tmp[] = array('value'=>$value,
                                       'processList'=>$dbinputs[$nodeid][$name]['processList'],
                                       'opt'=>array('sourcetype'=>"WATTSUP",
                                                    'sourceid'=>$dbinputs[$nodeid][$name]['id']));
                    }
                }
            }

            // Actuall insert all of the data to the process
            foreach ($tmp as $i) {
                $process->input($time, $i['value'], $i['processList'], $i['opt']);
            }

        }

        if ($valid) {
            $result = 'ok';
        } else {
            $result = "Error: $error\n";
        }
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
