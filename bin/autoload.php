<?php
// add library/ into include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(realpath(dirname(__FILE__)) . '/../src/'),
    get_include_path(),
)));

/**
 * Implementing PSR-0 standard namespace autoloading - @see http://www.sitepoint.com/autoloading-and-the-psr-0-standard/
 *
 * If switched onto some framework, this might be done there already, so comment it out. Also additional library used
 * by google can be utilized: @see https://gist.github.com/jwage/221634
 *
 * this autoloading is not a part of PHP 5.4 and even 5.5 - some *** in PHP groups don't want it included. :-(
 */
spl_autoload_register(
    function ($className) {
        $className = ltrim($className, '\\');
        $fileName  = '';
        $namespace = '';
        if ($lastNsPos = strrpos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        require $fileName;
    }
);

// make composer autoload magic
require __DIR__ . '/../vendor/autoload.php';
