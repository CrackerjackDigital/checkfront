<?php
class CheckfrontCryptoTest extends CheckfrontTest {

    public $description = 'Test Checkfront module Crypto functions using CryptoDefuse';


    private $injectorConfig = array(
        /*  e.g. serviceName => className
                'CheckfrontCryptoService' => 'CheckfrontCryptoDefuse'
        */
    );

    private $plainText = <<<PLAINTEXT
        The woods are lovely dark and deep,
        but I have promises to keep,
        and miles to go before I sleep,
        and miles to go before I sleep.

        Synbols: !@#$%^&*()_{}}{[\][;'l';/.,/.,|}{":?><
        Unicode: ā₣℞ℳ<⊅∄∝'‘’‛?§″′"'˝„°❞❝
PLAINTEXT;

    private $token = array(
        20,
        '2015-10-01',
        '2015-10-15'
    );

    private $cryptoClasses = array(
        'CheckfrontCryptoDefuse',
        'CheckfrontCryptoZend'
    );

    /**
     * Give the IDE some type hinting/autocomplete help.
     *
     * @return $this|PHPUnit_Framework_Assert
     */
    public function __invoke() {
        return $this;
    }

    public function setUp() {
        parent::setUp();
        Injector::nest();
    }

    public function tearDown() {
        Injector::unnest();
        parent::tearDown();
    }

    public function testNativeFunctions() {
        // test for each service registered
        foreach ($this->cryptoClasses as $className) {

            $this->configureCryptoService($className);

            $crypto = CheckfrontModule::crypto();

            $key = $crypto->generate_key();

            $encrypted = $crypto->encrypt_native($this->plainText, $key);

            $decrypted = $crypto->decrypt_native($encrypted, $key);

            $this()->assertEquals($decrypted, $this->plainText, "That decrypted value equals config.plain_text");
        }
    }

    public function testBasicFunctions() {
        // test for each service registered
        foreach ($this->cryptoClasses as $className) {

            $this->configureCryptoService($className);

            $crypto = CheckfrontModule::crypto();

            // run for no and with access key
            foreach (array(null, $crypto->generate_key()) as $accessKey) {

                $encrypted = $crypto->encrypt($this->plainText, $accessKey);

                $decrypted = $crypto->decrypt($encrypted, $accessKey);

                $this()->assertEquals($decrypted, $this->plainText, "That decrypted value equals config.plain_text");
            }
        }
    }

    public function testTokenFunctions() {
        list($itemID, $startDate, $endDate) = $this->token;

        foreach ($this->cryptoClasses as $className) {

            $this->configureCryptoService($className);

            $crypto = CheckfrontModule::crypto();

            // run for no and with access key
            foreach (array(null, $crypto->generate_key()) as $accessKey) {

                $encrypted = $crypto->encrypt_token(
                    $itemID,
                    $startDate,
                    $endDate,
                    $accessKey
                );
                list($id, $start, $end) = $crypto->decrypt_token($encrypted, $accessKey);

                $this()->assertEquals($itemID, $id, "Assert that '$itemID' = '$id'");
                $this()->assertEquals($startDate, $start, "Assert that '$startDate' = '$start'");
                $this()->assertEquals($endDate, $end, "Assert that '$endDate' = '$end'");
            }
        }
    }

    protected function configureCryptoService($className) {
        /** @var CheckfrontCryptoInterface $instance */
        $instance = $className::create();

        Injector::inst()->registerService(
            $instance,
            'CheckfrontCryptoService'
        );
        $serverKeyConfigName = $instance->config()->get('server_key_config_name');
        Config::inst()->update($className, $serverKeyConfigName, $instance->generate_key());
    }

}