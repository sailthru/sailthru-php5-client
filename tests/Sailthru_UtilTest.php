<?php
require_once "_autoload.php";

class Sailthru_UtilTest extends PHPUnit_Framework_TestCase {
    public function testVerifyPurchaseItemsWhenAllRequiredItemFieldsExist() {
        $items = array(
            array('id' => 11, 'price' => 26262, 'qty' => '11', 'url' => 'http://xyx.com/abc', 'title' => 'some title'),
            array('id' => 171, 'price' => 262, 'qty' => '18', 'url' => 'http://xyz.com/abc', 'title' => 'some title2')
        );
        $this->assertTrue(Sailthru_Util::verifyPurchaseItems($items));
    }

    public function testVerifyPurchaseItemsWhenOneofTheRequiredItemFieldDontExist() {
        $items = array(
            array('id' => 11, 'price' => 26262, 'qty' => '11', 'url' => 'http://xyx.com/abc'),  //title is missing
            array('id' => 171, 'price' => 262, 'qty' => '18', 'url' => 'http://xyz.com/abc', 'title' => 'some title2')
        );
        $this->assertFalse(Sailthru_Util::verifyPurchaseItems($items), "aaa");
    }
}