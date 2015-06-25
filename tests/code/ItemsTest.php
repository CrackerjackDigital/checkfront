<?php


class CheckfrontItemsTest extends SapphireTest {

    public function testItems() {
        $api = CheckfrontModule::api();
        $result = $api->fetchItems('today');
    }
}