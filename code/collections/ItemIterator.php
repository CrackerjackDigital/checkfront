<?php

class CheckfrontItemIterator extends ArrayIterator {
    public function __construct(array $array, $action = CheckfrontItemModel::DefaultFromAction, $flags = 0) {
        parent::__construct($array, $flags);
    }
    public function current() {
        return CheckfrontItemModel::create_from_checkfront(parent::current(), $this->action);
    }
}