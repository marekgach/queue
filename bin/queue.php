<?php

@include_once __DIR__ . '/autoload.php';

(@include_once __DIR__ . '/../vendor/autoload.php') || @include_once __DIR__ . '/../../../autoload.php';

$directories = array(getcwd(), getcwd() . DIRECTORY_SEPARATOR . 'config');

$configFile = null;
foreach ($directories as $directory) {
    $configFile = $directory . DIRECTORY_SEPARATOR . 'kurzor-config.php';

    if (file_exists($configFile)) {
        break;
    }
}

if ( ! file_exists($configFile)) {
    \Kurzor\Tools\Console\ConsoleRunner::printCliConfigTemplate();
    exit(1);
}

if ( ! is_readable($configFile)) {
    echo 'Configuration file [' . $configFile . '] does not have read permission.' . "\n";
    exit(1);
}

$commands = array();

$helperSet = require $configFile;

if ( ! ($helperSet instanceof \Symfony\Component\Console\Helper\HelperSet)) {
    foreach ($GLOBALS as $helperSetCandidate) {
        if ($helperSetCandidate instanceof \Symfony\Component\Console\Helper\HelperSet) {
            $helperSet = $helperSetCandidate;
            break;
        }
    }
}

\Kurzor\Tools\Console\ConsoleRunner::run($helperSet, $commands);