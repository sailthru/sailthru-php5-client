<?php
 function show_response($response){
    if ($response){
        echo "<pre>";
        print_r($response);
        echo "</pre>";
    } else {
        echo "Sorry, there was an error!";
    }
}