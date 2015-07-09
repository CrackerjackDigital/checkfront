<?php


class CheckfrontPackageTest extends CheckfrontTest {

    public function __invoke() {
        return $this;
    }

    /**
     *
     */
    public function testListPackages() {
        $this->loadConfig();
        /** @var CheckfrontAPIPackagesResponse $response */
        $response = CheckfrontModule::api()->listPackages();
        $packages = $response->getPackages();
        $info = $packages->map('ItemID', 'Title');
        $this->assertContains('Package A', $info);

    }
}