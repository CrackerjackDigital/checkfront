<?php


class CheckfrontPackageTest extends CheckfrontTest {

    public function __invoke() {
        return $this;
    }
    public function testPackage() {
        $this->configureCryptoService('CheckfrontCryptoDefuse');
    }
}