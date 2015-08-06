<?php

class CheckfrontItemsList extends ArrayList {
    public function getIterator() {
        return new CheckfrontItemIterator($this);
    }
}