<?php

namespace digitaldiff\et\models;

use craft\base\Model;
use craft\db\Query;

class FetchDataModel extends Model
{
    public function getTableRows(string $tableName): array
    {
        $query = (new Query())
            ->select('*')
            ->from($tableName);

        return $query->all();
    }
}