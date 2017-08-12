<?php

class Sailthru_Client_ExceptionTest extends PHPUnit_Framework_TestCase {
    public function testSailthru_Client_Exception_IsThrownWithCurlError() {
        $expectedExceptionMessage = 'Curl error: ';

        $this->setExpectedException(
            'Sailthru_Client_Exception',
            $expectedExceptionMessage,
            1003
        );

        $api_key = "invalid_key";
        $api_secret = "invalid_secret";
        $api_url = "http://foo.invalid"; // .invalid is reserved as an invalid TLD, see https://en.wikipedia.org/wiki/.invalid

        $sailthruClient = new Sailthru_Client($api_key, $api_secret, $api_url);
        $sailthruClient->getEmail("praj@sailthru.com");
    }

    public function testSailthru_Client_Exception_IsThrownWithEmptyResponse() {
        $this->markTestSkipped('This cannot be tested without either mocking curl or having a public server which returns us an empty response.');

        $expectedExceptionMessage = 'Bad response received from';

        $this->setExpectedException(
            'Sailthru_Client_Exception',
            $expectedExceptionMessage,
            1002
        );
    }
}
