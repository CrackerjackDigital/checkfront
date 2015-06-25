<?php

class CheckfrontSlip extends DBField {

    /**
     * Add the field to the underlying database.
     */
    public function requireField()
    {
        DB::requireField(
            $this->tableName,
            $this->name,
            'mediumblob'
        );
    }
}