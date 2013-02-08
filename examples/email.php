<?php
    require('config.php');
        
    /*
     * The variables below are used as examples.  Feel free to replace them to 
     * test out Sailthru's API.  Just remember to set your API key and API secret 
     * in the config.php file. 
     */
    $email = 'kjuantuah@sailthru.com';
    $verified = 0;
    $optout = 'none';
    $lists = array(
        'sailthru-everyone' => 1,
        'sailthru-customer-support' => 1,
        'sailthru-support' => 1,
        'sailthru-dev' => 0,
    );
    $templates = array(
        'Confirmation' => 1, 
        'Daily-Digest' => 0,
        'Weekly-Newsletter' => 1,
        'This-Week-at-Sailthru' => 1
    );
    $send = 'Example-Welcome-Demo';
    $send_vars = array(
        //These vars will replace vars set in template
        'email' => $email,
        'subject' => "Testing Sailthru's API",
        'name' => "Sailthru's Awesome Support Team",
        'url' => 'https://github.com/sailthru/sailthru-php5-client/tree/implementation/examples',
        'custom_text' => "<h1>You can place custom text here...</h1>"
    );
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
    $vars['text_only'] = 0;
    $sms = '+87781286689';
    $twitter = 'sailthru';
    $new_email = 'support+test@sailthru.com';
    $change_email = array('change_email' => $email, 'email' => $new_email);
    
    //Example using sailthru Library    
    try {
        //Set replacement vars and/or list subscriptions for an email address.
        $response = $sailthru->setEmail($email, $vars, $lists, $templates, $verified, $optout, $send, $send_vars);
        show_response($response);
        
        //Return information about an email address, including replacement vars and lists.
        $response = $sailthru->getEmail($email);
        show_response($response);
        
    } catch (Sail_Client_Exception $e) {
        // deal with exception
    }
