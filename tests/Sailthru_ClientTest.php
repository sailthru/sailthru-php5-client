<?php
require(__DIR__ . '/../sailthru/Sailthru_Client.php');
require(__DIR__ . '/../sailthru/Sailthru_Client_Exception.php');
require(__DIR__ . '/../sailthru/Sailthru_Util.php');

class Sailthru_ClientTest extends PHPUnit_Framework_TestCase {
    public function setUp() {
        $this->api_key = 'my_api_key';
        $this->secret = 'my_secret';
        $this->api_url = 'https://api.sailthru.com';
        $this->sailthru_client = new Sailthru_Client($this->api_key, $this->secret, $this->api_url);
    }

    public function testDefaultTimeoutParameter() {
        $this->sailthru_client = new Sailthru_Client($this->api_key, $this->secret, $this->api_url);
        $this->assertTrue($this->sailthru_client->getTimeout() == 10000);
        $this->assertTrue($this->sailthru_client->getConnectTimeout() == 10000);
    }

    public function testCustomTimeoutParameter() {
        $this->sailthru_client = new Sailthru_Client($this->api_key, $this->secret, $this->api_url,
                                                     array('timeout' => 1, 'connect_timeout' => 2));
        $this->assertTrue($this->sailthru_client->getTimeout() == 1);
        $this->assertTrue($this->sailthru_client->getConnectTimeout() == 2);
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

    public function testApiPostWithValidJsonResponse() {
        $mock = $this->getMock('Sailthru_Client', array('apiPost'), array($this->api_key, $this->secret, $this->api_url));
        $json_response = array(
            'email' => 'praj@infynyxx.com',
            'profile_id' => '4f284c28a3a627b6389bfb4c',
            'verified' => 0,
            'vars' => array(
                'name' => 'Prajwal Tuladhar'
            )
        );
        $mock->expects($this->once())
            ->method('apiPost')
            ->will($this->returnValue($json_response));
        $this->assertTrue(is_array($mock->apiPost('email', $json_response)));
    }

    
    /**
     * @expectedException Sailthru_Client_Exception
     */
    public function testApiPostWithInvalidJsonResponse() {
        $mock = $this->getMock('Sailthru_Client', array('apiPost'), array($this->api_key, $this->secret, $this->api_url));
        $json_response = 'invalid JSON';
        $mock->expects($this->once())
            ->method('apiPost')
            ->will($this->throwException(new Sailthru_Client_Exception()));
        $response = $mock->apiPost('email', array('email' => 'praj@infynyxx.com'));
        $this->assertTrue(is_array($response)); // this will never be called
    }

    public function testPrepareJsonPayload() {
        $method = new ReflectionMethod('Sailthru_Client', 'prepareJsonPayload');
        $method->setAccessible(true);
        $json_payload_without_binary_data = array(
            'email' => 'praj@infynyxx.com',
            'vars' => array(
                'name' => 'Prajwal Tuladhar'
            ),
            'action' => 'user'
        );
        $invoked = $method->invoke($this->sailthru_client, $json_payload_without_binary_data);
        $this->assertEquals($invoked['api_key'], $this->api_key);
        $this->assertTrue(isset($invoked['sig']));
    }

    public function testPrepareJsonPayloadWithBinaryData() {
        $method = new ReflectionMethod('Sailthru_Client', 'prepareJsonPayload');
        $method->setAccessible(true);
        $json_payload = array(
            'email' => 'praj@infynyxx.com',
            'vars' => array(
                'name' => 'Prajwal Tuladhar'
            ),
            'action' => 'user'
        );
        $binary_data_param = array('file' => '/tmp/file.txt');
        $invoked = $method->invoke($this->sailthru_client, $json_payload, $binary_data_param);
        $this->assertEquals($invoked['api_key'], $this->api_key);
        $this->assertEquals($invoked['file'], $binary_data_param['file']);
    }
}
