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

    require_once "Modules/process/process_model.php";
    $process = new Process($mysqli, $input, $feed, $user->get_timezone($session['userid']));

    // Process /wattsup/post.text messages from Watts Up? .net
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

            $watts    = get('w');
            $volts    = get('v');
            $amps     = get('a');
            $watth    = get('wh');
            $maxwatts = get('wmx');
            $maxvolts = get('vmx');
            $maxamps  = get('amx');
            $minwatts = get('wmi');
            $minvolts = get('vmi');
            $minamps  = get('ami');
            $pf       = get('pf');
            $pcy      = get('pcy');
            $freq     = get('frq');
            $voltamps = get('va');

            # Only include fields we actually got
            if (is_numeric($watts))    $data['watts']        = $watts / 10;
            if (is_numeric($volts))    $data['volts']        = $volts / 10;
            if (is_numeric($amps))     $data['amps']         = $amps / 1000;
            if (is_numeric($watth))    $data['watt_hours']   = $watth / 1000;
            if (is_numeric($maxwatts)) $data['max_watts']    = $maxwatts / 10;
            if (is_numeric($maxvolts)) $data['max_volts']    = $maxvolts / 10;
            if (is_numeric($maxamps))  $data['max_amps']     = $maxamps / 1000;
            if (is_numeric($minwatts)) $data['min_watts']    = $minwatts / 10;
            if (is_numeric($minvolts)) $data['min_volts']    = $minvolts / 10;
            if (is_numeric($minamps))  $data['min_amps']     = $minamps / 1000;
            if (is_numeric($pf))       $data['power_factor'] = $pf;
            if (is_numeric($pcy))      $data['power_cycle']  = $pcy;
            if (is_numeric($freq))     $data['freq']         = $freq / 10;
            if (is_numeric($voltamps)) $data['volt_amps']    = $voltamps / 10;

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

            // Actually insert all of the data to the process
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


    return array('content'=>$result);
}
