<?php
    require('config.php');
        
    //variables to use
    $id = 'support@sailthru.com';
    $key = 'email';
    $fields = array(
            'keys' => 1,
            'vars' => 1,
            'lists' => 1,
            'engagement' => 1,
            'optout' => 1
        );    
    $keys = array(
        'email' => 'support@sailthru.com',
        /*
         * The variables below have to be enabled to work.
         * Check out our documentation at http://getstarted.sailthru.com/api/user
         * or contact your Account Manager to have them enabled.
         */
        //'twitter' => 'infynyxx',          
        //'fb' => 726310296
        //'extid' => 
    );
    $keysconflict = 'merge';
    $vars = array(
            'firstName' => 'Support',
            'lastName' => 'Sailthru',
            'phone_number' => '877-812-8689',
            'website' => 'https://www.sailthru.com/',
            'address' => '160 Varick Street',
            'city' => 'New York',
            'zip' => '10014',
            'support' => array(
                        'api-support' => 1,
                        'user-interface' => 1,
                        'magento' => 0,
                        'salesforce' => 0
            )
    );
    $lists = array(
        'sailthru-everyone' => 1,
        'sailthru-customer-support' => 0,
        'sailthru-support' => 0,
        'sailthru-dev' => 0,
    );
    $optout_email = 'none';
    $login = 'site';
    
    
    
    //Example using sailthru Library    
    try {
        // create new user profile
        $response = $sailthru->createNewUser(array(
            'key' => $key, 
            'fields' => $fields, 
            'keys' => $keys, 
            'vars' => $vars, 
            'lists' => $lists
            )
        );
        //show_response($response);
        
        $sid = $response['keys']['sid'];
        $cookie = $response['keys']['cookie'];
        
        //get user by Sailthru ID
        $response = $sailthru->getUseBySid($sid, $fields);
        //show_response($response);
        
        //get user by Custom key
        $response = $sailthru->getUserByKey($keys['email'], $key, $fields);
        //show_response($response); 
        
        //update existing user by Sailthru ID
        $response = $sailthru->saveUser($sid, array('keys' => $keys, 'lists' => $lists));
        //show_response($response);
        
        //update existing user by email
        $response = $sailthru->saveUser($id, array('key' => 'email', 'lists' => $lists));
        //show_response($response);
        
    } catch (Sail_Client_Exception $e) {
        // deal with exception
    }
