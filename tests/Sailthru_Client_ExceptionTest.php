<?php

use PHPUnit\Framework\TestCase;

class Sailthru_Client_ExceptionTest extends TestCase {
    public function testSailthru_Client_Exception() {
        $this->expectException(Sailthru_Client_Exception::class);
        $api_key = "invalid_key";
        $api_secret = "invalid_secret";
        // An invalid loopback port fails locally without contacting an external API.
        $api_url = "http://127.0.0.1:0";
        $sailthruClient = new Sailthru_Client($api_key, $api_secret, $api_url);
        $sailthruClient->getEmail("praj@sailthru.com");
    }
}
