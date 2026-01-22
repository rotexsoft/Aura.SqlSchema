<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Rotexsoft\SqlSchema;

use PDO;

/**
 *
 * Abstract schema discovery tools.
 *
 * @package Aura.SqlSchema
 *
 */
abstract class AbstractSchema implements SchemaInterface
{
    /**
     *
     * The quote prefix for identifier names.
     *
     *
     */
    protected string $quote_name_prefix = '"';

    /**
     *
     * The quote suffix for identifier names.
     *
     *
     */
    protected string $quote_name_suffix = '"';

    /**
     *
     * Constructor.
     *
     * @param PDO $pdo A database connection.
     *
     * @param ColumnFactory $column_factory A column object factory.
     *
     */
    public function __construct(protected \PDO $pdo, protected \Rotexsoft\SqlSchema\ColumnFactory $column_factory)
    {
    }

    /**
     *
     * Returns the column factory object.
     *
     */
    public function getColumnFactory(): ColumnFactory
    {
        return $this->column_factory;
    }

    /**
     *
     * Given a column specification, parse into datatype, size, and
     * decimal scale.
     *
     * @param string $spec The column specification; for example,
     * "VARCHAR(255)" or "NUMERIC(10,2)".
     *
     * @return array A sequential array of the column type, size, and scale.
     *
     */
    protected function getTypeSizeScope(string $spec): array
    {
        $spec  = strtolower($spec);
        $size  = null;
        $scale = null;

        // find the parens, if any
        $pos = strpos($spec, '(');
        if ($pos === false) {
            // no parens, so no size or scale
            $type = $spec;
        } else {
            // find the type first.
            $type = substr($spec, 0, $pos);

            // there were parens, so there's at least a size.
            // remove parens to get the size.
            $size = trim(substr($spec, $pos), '()');

            // a comma in the size indicates a scale.
            $pos = strpos($size, ',');
            if ($pos !== false) {
                $scale = substr($size, $pos + 1);
                $size  = substr($size, 0, $pos);
            }
        }

        return [$type, $size, $scale];
    }

    /**
     *
     * Splits an identifier name into two parts, based on the location of the
     * first dot.
     *
     * @param string $name The identifier name to be split.
     *
     * @return array An array of two elements; element 0 is the parts before
     * the dot, and element 1 is the part after the dot. If there was no dot,
     * element 0 will be null and element 1 will be the name as given.
     *
     */
    protected function splitName(string $name)
    {
        $pos = strpos($name, '.');
        if ($pos === false) {
            return [null, $name];
        } else {
            return [substr($name, 0, $pos), substr($name, $pos+1)];
        }
    }

    /**
     *
     * Quotes a single identifier name (table, table alias, table column,
     * index, sequence).
     *
     * If the name contains `' AS '`, this method will separately quote the
     * parts before and after the `' AS '`.
     *
     * If the name contains a space, this method will separately quote the
     * parts before and after the space.
     *
     * If the name contains a dot, this method will separately quote the
     * parts before and after the dot.
     *
     * @param string $name The identifier name to quote.
     *
     * @return string The quoted identifier name.
     *
     * @see replaceName()
     *
     */
    public function quoteName(string $name): string
    {
        // remove extraneous spaces
        $name = trim($name);

        // "name"."name"
        $pos = strrpos($name, '.');
        if ($pos !== false) {
            $one = $this->quoteName(substr($name, 0, $pos));
            $two = $this->quoteName(substr($name, $pos + 1));
            return "{$one}.{$two}";
        }

        // "name"
        return $this->quote_name_prefix . $name . $this->quote_name_suffix;
    }

    /**
     *
     * Fetch all result rows.
     *
     * @param string $statement The SQL statement.
     *
     * @param array $values Values to bind to the SQL statement.
     *
     * @return array
     *
     */
    protected function pdoFetchAll($statement, array $values = [])
    {
        $sth = $this->pdo->prepare($statement);
        $sth->execute($values);
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     *
     * Fetch the first column of all result rows.
     *
     * @param string $statement The SQL statement.
     *
     * @param array $values Values to bind to the SQL statement.
     *
     * @return array
     *
     */
    protected function pdoFetchCol($statement, array $values = [])
    {
        $sth = $this->pdo->prepare($statement);
        $sth->execute($values);
        return $sth->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     *
     * Fetch the first column of the first row.
     *
     * @param string $statement The SQL statement.
     *
     * @param array $values Values to bind to the SQL statement.
     *
     * @return mixed
     *
     */
    protected function pdoFetchValue($statement, array $values = [])
    {
        $sth = $this->pdo->prepare($statement);
        $sth->execute($values);
        return $sth->fetchColumn(0);
    }
}
