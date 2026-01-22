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
 * MySQL schema discovery tools.
 *
 * @package Aura.SqlSchema
 *
 * @psalm-suppress UnusedClass
 */
class MysqlSchema extends AbstractSchema
{
    protected bool $maria = false; // if the current DB server is mariadb
    /**
     *
     * The quote prefix for identifier names.
     *
     *
     */
    protected string $quote_name_prefix = '`';

    /**
     *
     * The quote suffix for identifier names.
     *
     *
     */
    protected string $quote_name_suffix = '`';
    
    /**
     * A cache to hold the results of the "SHOW VARIABLES LIKE '%version%'"
     * query so it only gets executed once for each pdo connection injected
     * into the constructor of this class. If multiple instances of this class
     * are instantiated with one unique pdo object, the query will only 
     * be run once on the first instance, the other instances will use the
     * cached result.
     */
    protected static array $varsMap = [];

    public function __construct(\PDO $pdo, ColumnFactory $column_factory) {
        parent::__construct($pdo, $column_factory);
                    
        $pdoObjId = \spl_object_hash($pdo);
        
        $vars = 
            !\array_key_exists($pdoObjId, static::$varsMap)
                ? $pdo->query("SHOW VARIABLES LIKE '%version%'")
                      ->fetchAll(\PDO::FETCH_KEY_PAIR) // fetch values from DB
                : static::$varsMap[$pdoObjId]; // use cached value
        
        // cache result for this pdo connection
        if(!\array_key_exists($pdoObjId, static::$varsMap)) {

            static::$varsMap[$pdoObjId] = $vars;
        }
        
        if (
            isset($vars['version']) 
            && str_contains(mb_strtolower((string) $vars['version'], 'UTF-8'), 'maria')
        ) {
            $this->maria = true;
        }
    }
    
    /**
     *
     * Returns a list of tables in the database.
     *
     * @param string $schema Optionally, pass a schema name to get the list
     * of tables in this schema.
     *
     * @return string[] The list of table-names in the database.
     *
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public function fetchTableList(?string $schema = null): array
    {
        $text = 'SHOW TABLES';
        if ($schema !== null) {
            $text .= ' IN ' . $this->quoteName(''.$schema);
        }

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
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArrayAccess
     * @psalm-suppress MixedArrayOffset
     * @psalm-suppress MixedArgument
     */
    public function fetchTableCols(string $spec): array
    {
        [$schema, $table] = $this->splitName($spec);
        
        $table = $this->quoteName((string)$table);
        $text = "SHOW COLUMNS FROM {$table}";

        if ($schema) {
            $schema = preg_replace('/[^\w]/', '', (string) $schema);
            $schema = $this->quoteName((string)$schema);
            $text .= " IN {$schema}";
        }

        // get the column descriptions
        $raw_cols = $this->pdoFetchAll($text);
        
        // where the column info will be stored
        $cols = [];

        // loop through the result rows; each describes a column.
        foreach ($raw_cols as $val) {

            $name = $val['Field'];
            [$type, $size, $scale] = $this->getTypeSizeScope((string)$val['Type']);
            
            $default_val = $this->getDefault($val['Default'], $val['Null'] == 'YES');
            
            if($this->maria) {
                
                if($val['Null'] == 'YES' && $default_val === 'NULL') {
                    
                    $default_val = null;
                }
                
                if(
                    (in_array(mb_strtolower((string) $type, 'UTF-8'), ['char', 'varchar', 'text']))
                    && $default_val === "''"
                ) {
                    $default_val = '';
                }
            }

            // save the column description
            $cols[$name] = $this->column_factory->newInstance(
                $name,
                $type,
                ($size  ? (int) $size  : null),
                ($scale ? (int) $scale : null),
                $val['Null'] != 'YES',
                $default_val,
                str_contains((string) $val['Extra'], 'auto_increment'),
                $val['Key'] == 'PRI'
            );
        }

        // done!
        return $cols;
    }

    /**
     *
     * A helper method to get the default value for a column.
     *
     * @param mixed $default The default value as reported by MySQL.
     *
     * @return mixed
     *
     */
    protected function getDefault(mixed $default, bool $nullable)
    {
        if ($this->maria && $nullable && $default === 'NULL') {
            return null;
        }
        
        $upper = strtoupper(($default ? ((string)$default) : ''));
        if ($upper === 'NULL' || $upper === 'CURRENT_TIMESTAMP' || ($this->maria && $upper === 'CURRENT_TIMESTAMP()') ) {
            // the only non-literal allowed by MySQL is "CURRENT_TIMESTAMP"
            return null;
        } else {
            // return the literal default
            return $default;
        }
    }
}
