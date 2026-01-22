# Rotexsoft/SqlSchema a fork of Aura.SqlSchema

Provides facilities to read table names and table columns from a database
using a [PDO](http://php.net/PDO) connection.

## Foreword

This fork has been fine-tuned to work with Mariadb which [Aura.SqlSchema](https://github.com/auraphp/Aura.SqlSchema) was not designed to support.
This fork has been tested against the following databases:
- Mariadb 10.4.x, 10.5.x, 10.6.x, 10.11.x, 11.0.x, 11.1.x & 11.2.x
- Mysql 5.6, 5.7, 8.0.x & 8.3.x 
- Postgres 12.x, 13.x, 14.x, 15.x & 16.x

Some future work will be done to make sure it works with Microsoft Sql Server

### Installation

This library requires PHP 8.1 or later; we recommend using the latest available version of PHP as a matter of principle. It has no userland dependencies.

It is installable and autoloadable via Composer as [rotexsoft/sqlschema](https://packagist.org/packages/rotexsoft/sqlschema).

Alternatively, [download a release](https://github.com/rotexdegba/Aura.SqlSchema/releases) or clone this repository, then require or include its _autoload.php_ file.

### Quality

[![Coverage Status](https://coveralls.io/repos/github/rotexdegba/Aura.SqlSchema/badge.svg?branch=rotexsoft-3.x)](https://coveralls.io/github/rotexdegba/Aura.SqlSchema?branch=rotexsoft-3.x)
[![Run PHP Tests and Code Quality Tools](https://github.com/rotexdegba/Aura.SqlSchema/actions/workflows/php.yml/badge.svg)](https://github.com/rotexdegba/Aura.SqlSchema/actions/workflows/php.yml)

To run the unit tests at the command line, issue `phpunit` at the package root. (This requires [PHPUnit][] to be available as `phpunit`.)

[PHPUnit]: http://phpunit.de/manual/

This library attempts to comply with [PSR-1][], [PSR-2][], and [PSR-4][]. If
you notice compliance oversights, please send a patch via pull request.

[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PSR-4]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md

### Branching

These are the branches in this repository:

- **2.x:** Corresponds to the **2.x** branch in the original [Aura.SqlSchema Repository](https://github.com/auraphp/Aura.SqlSchema). There's no plan to actively maintain this branch, but it could be synced with the [Aura.SqlSchema Repository](https://github.com/auraphp/Aura.SqlSchema) (i.e. changes to the **2.x** branch in that repo can be pulled in).


- **php-8-deprecation-fixes:** this branch was started from the **2.x** branch and contains changes to make the code-base compliant with PHP 8.1+ & increased the minimum PHP version to 8.1. It was meant as a branch to be contributed to the [Aura.SqlSchema Repository](https://github.com/auraphp/Aura.SqlSchema). See https://github.com/auraphp/Aura.SqlSchema/issues/22


- **rotexsoft-3.x:** this branch was started from the **php-8-deprecation-fixes** branch. It is no longer compatible with the original [Aura.SqlSchema package](https://github.com/auraphp/Aura.SqlSchema) due to the following changes:
  - changed the **Aura\SqlSchema** namespace to **Rotexsoft\SqlSchema**
  - more stricter type-hinting applied across the code-base, leading to changes in some of the interface method signatures.
  >This is going to be the branch in which code for version 3.x releases of **rotexsoft/sqlschema** will reside


There will be future branches like **rotexsoft-4.x** & the likes for versions 4.x & above.

### Testing

There should be a **./phpunit.xml** file if you have run the **composer update** or **composer install** command at least once.

Edit the **./phpunit.xml** to contain the correct database connection info for MySql & Postgres or comment out the MySql & Postgres variables to only test against Sqlite and the run the command below to test:

```
./vendor/bin/phpunit
```

If you are running on a Linux OS with podman installed, you can run the **./run-tests-against-multiple-db-versions.php** script to do a more extensive test against multiple versions of MariaDB, MySql & Postgres.

## Getting Started

### Instantiation

Instantiate a driver-specific schema object with a matching
[PDO](http://php.net/PDO) instance:

```php
<?php
use Rotexsoft\SqlSchema\ColumnFactory;
use Rotexsoft\SqlSchema\MysqlSchema; // for MySQL
use Rotexsoft\SqlSchema\PgsqlSchema; // for PostgreSQL
use Rotexsoft\SqlSchema\SqliteSchema; // for Sqlite
use Rotexsoft\SqlSchema\SqlsrvSchema; // for Microsoft SQL Server
use PDO;

// a PDO connection
$pdo = new PDO(...);

// a column definition factory
$column_factory = new ColumnFactory();

// the schema discovery object
$schema = new MysqlSchema($pdo, $column_factory);
?>
```

### Fetching Table Lists

To get a list of tables in the database, issue `fetchTableList()`:

```php
<?php
$tables = $schema->fetchTableList();
foreach ($tables as $table) {
    echo $table . PHP_EOL;
}
?>
```

### Fetching Column Information

To get information about the columns in a table, issue `fetchTableCols()`:

```php
<?php
$cols = $schema->fetchTableCols('table_name');
foreach ($cols as $name => $col) {
    echo "Column $name is of type "
       . $col->type
       . " with a size of "
       . $col->size
       . PHP_EOL;
}
?>
```

Each column description is a `Column` object with the following properties:

- `name`: (string) The column name

- `type`: (string) The column data type.  Data types are as reported by the database.

- `size`: (int) The column size.

- `scale`: (int) The number of decimal places for the column, if any.

- `notnull`: (bool) Is the column marked as `NOT NULL`?

- `default`: (mixed) The default value for the column. Note that sometimes
  this will be `null` if the underlying database is going to set a timestamp
  automatically.

- `autoinc`: (bool) Is the column auto-incremented?

- `primary`: (bool) Is the column part of the primary key?

