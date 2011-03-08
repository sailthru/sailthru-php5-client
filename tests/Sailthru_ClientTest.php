<?php
require_once "_autoload.php";
class Sailthru_ClientTest extends PHPUnit_Framework_TestCase {
    public function setUp() {
        $this->api_key = 'my_api_key';
        $this->secret = 'my_secret';
        $this->api_url = 'http://api.sailthru-sb.com';
        $this->sailthru_client = new Sailthru_Client($this->api_key, $this->secret, $this->api_url);
    }

    public function testResponseFromServerIsOfArrayTypeWhenFormatIsPHP() {
        $response = $this->sailthru_client->getEmail("xyz@xyz.com");
        $this->assertTrue(is_array($response));
    }

    public function testResponseFromServerIsOfJSONObjectTypeWhenFormatIsJSON() {
        $response = $this->sailthru_client->getList("some-list", "json");
        json_decode($response);
         $this->assertTrue(is_int(json_last_error()));
    }

    public function testSendWhenTemplateNameIsInvalid() {
        $template_name = 'invalid_template';
        $json_response = json_encode(array('error' => 14, 'errormsg' => 'Unknown template: ' . $template_name));
        $email = 'praj@sailthru.com';
        $mock = $this->getMock('Sailthru_Client', array('send'), array($this->api_key, $this->secret, $this->api_url));
        $mock->expects($this->once())
                ->method('send')
                ->will($this->returnValue($json_response));
         $this->assertEquals($json_response, $mock->send($template_name, 'praj@sailthru.com'));
    }


    public function testSendWhenTemplateIsValid() {
        $template_name = 'my_template';
        $email = 'praj@sailthru.com';
        $json_response = json_encode(array('email' => $email, 'send_id' => 'some_unique_id', 'template' => $template_name, 'status' => 'unknown'));
        $mock = $this->getMock('Sailthru_Client', array('send'), array($this->api_key, $this->secret, $this->api_url));
        $mock->expects($this->once())
                ->method('send')
                ->will($this->returnvalue($json_response));
        $this->assertEquals($json_response, $mock->send($template_name, $email));
    }
}
