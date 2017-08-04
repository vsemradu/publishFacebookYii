<?php

namespace common\components;


use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;


class QueryComponent extends Component
{
    public static function batchUpdate($table, $columns, $rows)
    {
        if (empty($rows)) {
            return '';
        }

        $schema = Yii::$app->db->getSchema();
        if (($tableSchema = $schema->getTableSchema($table)) !== null) {
            $columnSchemas = $tableSchema->columns;
        } else {
            $columnSchemas = [];
        }
        $primaryKey = $tableSchema->primaryKey[0];

        $values = [];
        foreach ($rows as $row) {
            $vs = [];
            $where = '';
            foreach ($row as $i => $value) {
                if ($i == $primaryKey && empty($where)) {
                    $where = "WHERE " . $primaryKey . "=" . $row[$primaryKey];
                    continue;
                }
                if (isset($columns[$i], $columnSchemas[$columns[$i]]) && !is_array($value)) {
                    $value = $columnSchemas[$columns[$i]]->dbTypecast($value);
                }
                if (is_string($value)) {
                    $value = $schema->quoteValue($value);
                } elseif ($value === false) {
                    $value = 0;
                } elseif ($value === null) {
                    $value = 'NULL';
                }

                $vs[] = $i . '=' . $value;
            }
            $values[] = 'UPDATE ' . $schema->quoteTableName($table) . ' SET ' . implode(', ', $vs) . ' ' . $where . ';';

        }
        if (!empty($values)) {
            Yii::$app->db->createCommand(implode(' ', $values))->execute();
        }

    }
}
