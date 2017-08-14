<?php

class Sailthru_Client_ExceptionTest extends PHPUnit_Framework_TestCase {

    private $api_key = "invalid_key";
    private $api_secret = "invalid_secret";
    private $bad_api_uri = "http://foo.invalid"; // .invalid is reserved as an invalid TLD, see https://en.wikipedia.org/wiki/.invalid

    public function testSailthru_Client_Exception_IsThrownWithCurlError() {
        $expectedExceptionMessage = 'Curl error: ';
        $this->setExpectedException(
            'Sailthru_Client_Exception',
            $expectedExceptionMessage,
            1002
        );

        $sailthruClient = new Sailthru_Client($this->api_key, $this->api_secret, $this->bad_api_uri);
        $sailthruClient->getUser("praj@sailthru.com");
    }

    public function testSailthru_Client_Exception_IsThrownWithoutCurlError() {
        $sailthruClient = new Sailthru_Client($this->api_key, $this->api_secret, $this->bad_api_uri);
        $mockHttpType = new ReflectionProperty("Sailthru_Client", "http_request_type");
        $mockHttpType->setAccessible(true);
        $mockHttpType->setValue($sailthruClient, "httpRequestWithoutCurl");

        $this->setExpectedException(
            'Sailthru_Client_Exception',
            'Stream error: ',
            1002
        );
        $sailthruClient->getUser("praj@sailthru.com");
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
