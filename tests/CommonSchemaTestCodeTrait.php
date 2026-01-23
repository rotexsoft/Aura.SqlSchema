<?php
namespace Rotexsoft\SqlSchema;

/**
 *
 * @author rotimi
 */
trait CommonSchemaTestCodeTrait {

    protected function setUp(): void
    {
        // skip if we don't have the extension
        if (! extension_loaded($this->extension)) {
            $this->markTestSkipped("Extension '{$this->extension}' not loaded.");
        }

        // database setup        
        $setup_class = 'Rotexsoft\SqlSchema\Setup\\' . ucfirst((string) $this->pdo_type) . 'Setup';
        
        $key = str_replace('\\', '_', $setup_class);
        
        if(
            !isset($GLOBALS["{$key}__dsn"])
            || !isset($GLOBALS["{$key}__username"])
            || !isset($GLOBALS["{$key}__password"])
        ) {
            $this->markTestSkipped('Skipping executing `' . static::class . '`. DB PDO credentials not set up in phpunit.xml  config' );
        }
        
        $this->setup = new $setup_class;

        // schema class same as this class, minus "Test"
        $class = substr(static::class, 0, -4);
        
        /** @var \PDO $pdo */
        $pdo = $this->setup->getPdo();
        
        $this->schema = new $class(
            $pdo,
            new ColumnFactory
        );
        
        if( $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ) {
            
            $version_number = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
            
            $is_mariadb = false;
            $vars = $pdo->query("SHOW VARIABLES LIKE '%version%'")
                        ->fetchAll(\PDO::FETCH_KEY_PAIR);

            if (
                isset($vars['version']) 
                && str_contains(mb_strtolower($vars['version'], 'UTF-8'), 'maria')
            ) {
                $is_mariadb = true;
            }
            
            if(!$is_mariadb && version_compare($version_number, '8.0.0', '>=')) {
                
                // integer size no longer needed in mysql 8+
                $this->expect_fetch_table_cols['id']['size'] = null;
            }

            if(
                (!$is_mariadb && version_compare($version_number, '8.0.0', '>='))
                || 
                ($is_mariadb  && version_compare($version_number, '10.11.6', '>='))
            ) {
                // timestamp column with column definition sql not explicitly 
                // specifying NOT NULL leads to the column being nullable 
                // in mysql 8+ & mariadb 10.11.6+
                $this->expect_fetch_table_cols['test_default_ignore']['notnull'] = false;
            }
        }
        
        // convert column arrays to objects
        foreach ($this->expect_fetch_table_cols as $name => $info) {
            $this->expect_fetch_table_cols[$name] = new Column(
                $info['name'],
                $info['type'],
                $info['size'],
                $info['scale'],
                $info['notnull'],
                $info['default'],
                $info['autoinc'],
                $info['primary']
            );
        }
    }

    public function testGetColumnFactory(): void
    {
        $actual = $this->schema->getColumnFactory();
        $this->assertInstanceOf(\Rotexsoft\SqlSchema\ColumnFactory::class, $actual);
    }

    public function testFetchTableList(): void
    {
        $actual = $this->schema->fetchTableList();
        $this->assertEquals($this->expect_fetch_table_list, $actual);
    }

    public function testFetchTableList_schema(): void
    {
        $schema2 = $this->setup->getSchema2();
        $actual = $this->schema->fetchTableList($schema2);
        $this->assertEquals($this->expect_fetch_table_list_schema, $actual);
    }

    public function testFetchTableCols(): void
    {
        $table  = $this->setup->getTable();
        $actual = $this->schema->fetchTableCols($table);
        $expect = $this->expect_fetch_table_cols;
        ksort($actual);
        ksort($expect);
        $this->assertSame(count($expect), count($actual));
        foreach (array_keys($expect) as $name) {
            $this->assertEquals($expect[$name], $actual[$name]);
        }
    }

    public function testFetchTableCols_schema(): void
    {
        $table  = $this->setup->getTable();
        $schema2 = $this->setup->getSchema2();
        $actual = $this->schema->fetchTableCols("{$schema2}.{$table}");
        $expect = $this->expect_fetch_table_cols;
        ksort($actual);
        ksort($expect);
        $this->assertSame(count($expect), count($actual));
        foreach ($expect as $name => $info) {
            $this->assertEquals($expect[$name], $actual[$name]);
        }
    }

    public function testQuoteName(): void
    {
        $actual = $this->schema->quoteName('one.two');
        $this->assertSame($this->expect_quote_name, $actual);
    }
}
