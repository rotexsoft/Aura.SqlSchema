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
 * An interface for schema discovery tools.
 *
 * @package Aura.SqlSchema
 *
 */
interface SchemaInterface
{
    /**
     *
     * Returns a list of tables in the database.
     *
     * @param string $schema Optionally, pass a schema name to get the list
     * of tables in this schema.
     *
     * @return string[] The list of table-names in the database.
     * 
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function fetchTableList(?string $schema = null): array;

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
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function fetchTableCols(string $spec): array;

    /**
     *
     * Returns the column factory object.
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getColumnFactory(): ColumnFactory;
}
