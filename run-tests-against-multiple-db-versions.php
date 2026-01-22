#!/usr/bin/env php
<?php
function readFromLine( $prompt = '' ) {
        
    echo $prompt;
    return trim(rtrim( fgets( STDIN ), PHP_EOL ));
}

function sleepWithEcho(int $seconds) {
    
    echo 'Sleeping....' . PHP_EOL;
    sleep($seconds);
    echo 'Waking....' . PHP_EOL;
}

function readableElapsedTime($microtime, $format = null, $round = 3) {
    
    if (is_null($format)) {
        $format = '%.3f%s';
    }

    if ($microtime >= 3600) {
        
        $unit = ' hour(s)';
        $time = round(($microtime / 3600), $round);
        
    } elseif ($microtime >= 60) {
        
        $unit = ' minute(s)';
        $time = round(($microtime / 60), $round);
        
    } elseif ($microtime >= 1) {
        
        $unit = ' second(s)';
        $time = round($microtime, $round);
        
    } else {
        
        $unit = 'ms';
        $time = round($microtime*1000);

        $format = preg_replace('/(%.[\d]+f)/', '%d', $format);
    }

    return sprintf($format, $time, $unit);
}

$new_line = PHP_EOL;
$console_prompt = "If you have an instance of (MySql / Mariadb) and / or Postgresql"
                . " running, please stop them and then press Enter.{$new_line}This"
                . " script will be spawning new container instances of MySql & Postgresql"
                . " that will be forwarding to ports 3306 and 5432 on this machine:";
$console_response = readFromLine($console_prompt); // hitting enter returns an empty string, 
                                                   // maybe later other options could be read
                                                   // into this variable     

$console_prompt2 = "Please enter a password that would be used for the Mysql & Mariadb root accounts (it must match the one specified in `phpunit.xml`):";
$mysql_root_psw = readFromLine(PHP_EOL . $console_prompt2);

$test_results =  [];
$container_creation_commands = [
    [
      'mysql:5.6.51' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mysql:5.6.51", 
      'postgres:12.22' => "podman run -dt -p 5432:5432 -e POSTGRES_HOST_AUTH_METHOD=trust docker.io/library/postgres:12.22", 
    ],
    [
      'mysql:5.7.44' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mysql:5.7.44", 
      'postgres:13.23' => "podman run -dt -p 5432:5432 -e POSTGRES_HOST_AUTH_METHOD=trust docker.io/library/postgres:13.23", 
    ],
    [
      'mysql:8.0.45' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mysql:8.0.45", 
      'postgres:14.20' => "podman run -dt -p 5432:5432 -e POSTGRES_HOST_AUTH_METHOD=trust docker.io/library/postgres:14.20", 
    ],
    [
      'mysql:8.4.8' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mysql:8.4.8", 
      'postgres:15.15' => "podman run -dt -p 5432:5432 -e POSTGRES_HOST_AUTH_METHOD=trust docker.io/library/postgres:15.15", 
    ],
    [
      'mysql:9.5.2' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mysql:9.5.2", 
      'postgres:16.11' => "podman run -dt -p 5432:5432 -e POSTGRES_HOST_AUTH_METHOD=trust docker.io/library/postgres:16.11", 
    ],
    
    // Maria db 
    [
      'mariadb:10.4.34' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mariadb:10.4.34", 
      'postgres:17.7' => "podman run -dt -p 5432:5432 -e POSTGRES_HOST_AUTH_METHOD=trust docker.io/library/postgres:17.7", 
    ],
    [
      'mariadb:10.5.29' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mariadb:10.5.29", 
      'postgres:18.1' => "podman run -dt -p 5432:5432 -e POSTGRES_HOST_AUTH_METHOD=trust docker.io/library/postgres:18.1", 
    ],
    [
      'mariadb:10.6.24' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mariadb:10.6.24", 
      'postgres:18.1' => "podman run -dt -p 5432:5432 -e POSTGRES_HOST_AUTH_METHOD=trust docker.io/library/postgres:18.1", 
    ],
    [
      'postgres:18.1' => "podman run -dt -p 5432:5432 -e POSTGRES_HOST_AUTH_METHOD=trust docker.io/library/postgres:18.1",
      'mariadb:10.11.15' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mariadb:10.11.15", 
    ],
    [
      'mariadb:11.4.9' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mariadb:11.4.9",
      'postgres:18.1' => "podman run -dt -p 5432:5432 -e POSTGRES_HOST_AUTH_METHOD=trust docker.io/library/postgres:18.1",  
    ],
    [
      'postgres:18.1' => "podman run -dt -p 5432:5432 -e POSTGRES_HOST_AUTH_METHOD=trust docker.io/library/postgres:18.1", 
      'mariadb:11.8.5' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mariadb:11.8.5", 
    ],
    [
      'mariadb:12.1.2' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mariadb:12.1.2",
      'postgres:18.1' => "podman run -dt -p 5432:5432 -e POSTGRES_HOST_AUTH_METHOD=trust docker.io/library/postgres:18.1", 
    ],
];
      
echo PHP_EOL . PHP_EOL . 'Starting to create containers and run tests....' . PHP_EOL . PHP_EOL;
      
$start_time = microtime(true);

foreach($container_creation_commands as $postgres_and_mysql_container_creation_command) {
    
    $db_versions = '';
    
    foreach($postgres_and_mysql_container_creation_command as $db_version => $command ) {
        
        $db_versions .= $db_version . PHP_EOL;
        
        $retval=null;
        $output=null;
        exec($command, $output, $retval);
        
        echo PHP_EOL . PHP_EOL;
    }
    
    sleepWithEcho(30); // allow databases to load properly

    echo PHP_EOL . PHP_EOL;
    
    $output = null;
    $phpunit_retval = null;
    $phpunit = __DIR__ . '/vendor/bin/phpunit --coverage-text';

    // Print out current db versions being tested with
    echo "Testing against the following databases:" .PHP_EOL . $db_versions . PHP_EOL;
    
    system($phpunit, $phpunit_retval);

    echo "Stoping container instances & deleting their images" .PHP_EOL . PHP_EOL;
    system("podman stop -a"); // stop the containers
    
    // remove their images from the machine to save disk space
    system("podman system prune --all --force");
    system("podman rmi --all");
    system("podman volume rm --all --force");
    
    if($phpunit_retval !== 0) {
        
        ////////////////////////////////////////////////////////////////////////
        // A PHPUnit Test failed.
        // if $phpunit_retval !== 0, test failed, stop containers and exit
        // see https://github.com/sebastianbergmann/phpunit/blob/10.5/src/TextUI/ShellExitCodeCalculator.php
        // for phpunit shell exit codes
        ////////////////////////////////////////////////////////////////////////
        echo PHP_EOL . "Test failed for the following databases:" . PHP_EOL . $db_versions;
        echo PHP_EOL . 'Goodbye!'. PHP_EOL;
        exit(1);
        
    } eLse {
        
        $test_results[] = $db_versions;
        echo PHP_EOL;
        
    } // if($phpunit_retval !== 0)
} // foreach($postgres_and_mysql_container_creation_commands as $postgres_and_mysql_container_creation_command)

$end_time = microtime(true);

$elapsed = $end_time - $start_time;

if (count($test_results) > 0) {
    
    echo PHP_EOL . PHP_EOL 
        . "Test passed for the following databases:" 
        . PHP_EOL . PHP_EOL;
    
    foreach ($test_results as $test_result) {

        echo $test_result . PHP_EOL;
        
    } // foreach ($test_results as $test_result)
} // if (count($test_results) > 0)

echo PHP_EOL . 'Time taken: ' . readableElapsedTime($elapsed). PHP_EOL. PHP_EOL;

echo PHP_EOL . 'Goodbye!'. PHP_EOL;
