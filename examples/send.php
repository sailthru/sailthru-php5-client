
<?php
    require('config.php');
       
    /*
     * The variables below are used as examples.  Feel free to replace them to 
     * test out Sailthru's API.  Just remember to set your API key and API secret 
     * in the config.php file. 
     */
    $template = 'Example-Newsletter';
    $email = 'sales@sailthru.com';
    $vars = array(
        'firstName' => 'Support',
        'lastName' => 'Sailthru'
    );
    $options = array(
        'replyto' => 'support@sailthru.com',
        'test' => 1,
        'behalf_email' => 'support+1@sailthru.com'
    );
    $schedule_time = 'NOW';
    
    //Example using sailthru Library    
    try {
        //Send an email using the send API call
        $response = $sailthru->send($template, $email, $vars, $options, $schedule_time);
        show_response($response);
    } catch (Sail_Client_Exception $e) {
        // deal with exception
    }