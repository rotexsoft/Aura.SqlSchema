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
 * Microsoft SQL Server schema discovery tools.
 *
 * @package Aura.SqlSchema
 *
 * @psalm-suppress UnusedClass
 */
class SqlsrvSchema extends AbstractSchema
{
    /**
     *
     * The quote prefix for identifier names.
     *
     *
     */
    protected string $quote_name_prefix = '[';

    /**
     *
     * The quote suffix for identifier names.
     *
     *
     */
    protected string $quote_name_suffix = ']';

    /**
     *
     * Returns a list of all tables in the database.
     *
     * @param string $schema Fetch tbe list of tables in this schema;
     * when empty, uses the default schema.
     *
     * @return string[] All table names in the database.
     *
     * @todo Honor the $schema param.
     *
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public function fetchTableList(?string $schema = null): array
    {
        $text = "SELECT name FROM sysobjects WHERE type = 'U' ORDER BY name";
        return $this->pdoFetchCol($text);
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
     * @todo Honor `schema.table` as the specification.
     *
     * @psalm-suppress MixedArrayAccess
     * @psalm-suppress MixedArrayOffset
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArgument
     */
    public function fetchTableCols(string $spec): array
    {
        // no need for $schema yet
        [, $table] = $this->splitName($spec);

        // get column info
        $text = "exec sp_columns @table_name = " . $this->quoteName((string)$table);
        $raw_cols = $this->pdoFetchAll($text);

        // get primary key info
        $text = "exec sp_pkeys @table_owner = " . ((string)$raw_cols[0]['TABLE_OWNER'])
              . ", @table_name = " . $this->quoteName((string)$table);
        $raw_keys = $this->pdoFetchAll($text);
        $keys = [];
        foreach ($raw_keys as $row) {
            $keys[] = $row['COLUMN_NAME'];
        }

        $cols = [];
        foreach ($raw_cols as $row) {

            $name = $row['COLUMN_NAME'];

            $pos = strpos((string) $row['TYPE_NAME'], ' ');
            $type = $pos === false ? $row['TYPE_NAME'] : substr((string) $row['TYPE_NAME'], 0, $pos);

            // save the column description
            $cols[$name] = $this->column_factory->newInstance(
                $name,
                $type,
                $row['PRECISION'],
                $row['SCALE'],
                ! $row['NULLABLE'],
                $row['COLUMN_DEF'],
                str_contains(strtolower((string) $row['TYPE_NAME']), 'identity'),
                in_array($name, $keys)
            );
        }

        return $cols;
    }
}
