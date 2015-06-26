<?php
use \Defuse\Crypto\Crypto as Crypto;

class CheckfrontCryptoTest extends SapphireTest {

    public $description = 'Test CheckfrontModule cryptographical functions';

    public function testModuleCryptoFunctions() {
        $accessKey = Crypto::createNewRandomKey();

        $token = CheckfrontModule::encode_token(
            $accessKey,
            123123,
            '2015-06-26',
            '2015-06-30'
        );
        list($itemID, $startDate, $endDate) = CheckfrontModule::decode_token(
            $accessKey,
            $token
        );
        $this->assertEquals($itemID, 123123, "Assert that '$itemID' = 123123");
        $this->assertEquals($startDate, '2015-06-26', "Assert that '$startDate' = '2015-06-26'");
        $this->assertEquals($endDate, '2015-06-30', "Assert that '$endDate' = '2015-06-30'");
    }

}