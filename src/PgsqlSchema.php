<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Rotexsoft\SqlSchema;

/**
 *
 * PostgreSQL schema discovery tools.
 *
 * @package Aura.SqlSchema
 *
 * @psalm-suppress UnusedClass
 */
class PgsqlSchema extends AbstractSchema
{
    /**
     *
     * Returns a list of all tables in the database.
     *
     * @param string $schema Fetch tbe list of tables in this schema;
     * when empty, uses the default schema.
     *
     * @return string[] All table names in the database.
     *
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public function fetchTableList(?string $schema = null): array
    {
        if ($schema !== null) {
            $cmd = "
                SELECT table_name
                FROM information_schema.tables
                WHERE table_schema = :schema
            ";
            $values = ['schema' => $schema];
        } else {
            $cmd = "
                SELECT table_schema || '.' || table_name
                FROM information_schema.tables
                WHERE table_schema != 'pg_catalog'
                AND table_schema != 'information_schema'
            ";
            $values = [];
        }

        return $this->pdoFetchCol($cmd, $values);
    }

    /**
     *
     * Returns an array of columns in a table.
     *
     * @param string $spec Return the columns in this table. This may be just
     * a `table` name, or a `schema.table` name.
     *
     * @return Column[] An associative array where the key is the column name
     * and the value is a Column object.
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArrayAccess
     * @psalm-suppress MixedArrayOffset
     */
    public function fetchTableCols(string $spec): array
    {
        [$schema, $table] = $this->splitName($spec);
        
        if($schema === null) {
           
            $schema = $this->fetchCurrentSchema();
        }

        $cmd = "
            SELECT
            
                columns.column_name as name,
                columns.data_type as type,
                COALESCE(
                    columns.character_maximum_length,
                    columns.numeric_precision
                ) AS size,
                columns.numeric_scale AS scale,
                CASE
                    WHEN columns.is_nullable = 'YES' THEN 0
                    ELSE 1
                END AS notnull,
                columns.column_default AS default,
                CASE
                    WHEN SUBSTRING(columns.COLUMN_DEFAULT FROM 1 FOR 7) = 'nextval' THEN 1
                    ELSE 0
                END AS autoinc,
                CASE
                    WHEN table_constraints.constraint_type = 'PRIMARY KEY' THEN 1
                    ELSE 0
                END AS primary
                
            FROM information_schema.columns
                LEFT JOIN information_schema.key_column_usage
                    ON columns.table_schema = key_column_usage.table_schema
                    AND columns.table_name = key_column_usage.table_name
                    AND columns.column_name = key_column_usage.column_name
                LEFT JOIN information_schema.table_constraints
                    ON key_column_usage.table_schema = table_constraints.table_schema
                    AND key_column_usage.table_name = table_constraints.table_name
                    AND key_column_usage.constraint_name = table_constraints.constraint_name
            WHERE columns.table_schema = :schema
            AND columns.table_name = :table
            ORDER BY columns.ordinal_position
        ";

        $bind_values = ['table' => $table, 'schema' => $schema ];

        // where the columns are stored
        $cols = [];

        // get the column descriptions
        $raw_cols = $this->pdoFetchAll($cmd, $bind_values);

        // loop through the result rows; each describes a column.
        foreach ($raw_cols as $val) {
            $name = $val['name'];
            $type = $val['type'];
            $size = $val['size'];
            $scale = $val['scale'];
            //[$type, $size, $scale] = $this->getTypeSizeScope($val['type']);
            
            $cols[$name] = $this->column_factory->newInstance(
                (string)$name,
                (string)$type,
                ($size  ? (int) $size  : null),
                ($scale ? (int) $scale : null),
                (bool) ($val['notnull']),
                $this->getDefault($val['default'], (string)$type, !((bool) ($val['notnull'])) ),
                str_starts_with((string) $val['default'], 'nextval'),
                (bool) ($val['primary'])
            );
        }

        // done
        return $cols;
    }

    /**
     *
     * Given a native column SQL default value, finds a PHP literal value.
     *
     * SQL NULLs are converted to PHP nulls.  Non-literal values (such as
     * keywords and functions) are also returned as null.
     *
     * @param mixed $default The column default SQL value.
     *
     * @return mixed A literal PHP value.
     * 
     * @psalm-suppress PossiblyUnusedParam
     */
    protected function getDefault(mixed $default, string $type, bool $nullable)
    {
        // null?
        if ($default === null || strtoupper((string)$default) === 'NULL') {
            return null;
        }

        // numeric literal?
        if (is_numeric($default)) {
            return $default;
        }

        // string literal?
        $k = substr((string)$default, 0, 1);
        if(($k === '"' || $k === "'") && str_contains((string)$default, '::')) {
            // find the trailing :: typedef
            $pos = strrpos((string)$default, '::');
            // also remove the leading and trailing quotes
            /** @psalm-suppress PossiblyFalseOperand */
            return substr((string)$default, 1, $pos-2);
        }

        return null;
    }
    
    /**
     * @psalm-suppress MixedInferredReturnType
     * @psalm-suppress MixedReturnStatement
     */
    public function fetchCurrentSchema() : string
    {
        return $this->pdoFetchValue('SELECT CURRENT_SCHEMA');
    }
}
