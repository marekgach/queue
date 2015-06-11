<?php


/**
 * Class TestHelper bootstrap class for phpunittests
 *
 */
class TestHelper
{
    /**
     * Set private / protected field value using \ReflectionProperty object.
     *
     * @static
     * @param mixed $object object to be used
     * @param string $fieldName object property name
     * @param mixed $value object property value to be set
     * @return mixed object instance
     */
    public static function setPrivateField($object, $fieldName, $value)
    {
        $refId = new \ReflectionProperty($object, $fieldName);
        $refId->setAccessible(true);
        $refId->setValue($object, $value);
        $refId->setAccessible(false);

        return $object;
    } // end: setPrivateField()


    /**
     * Get private / protected field value using \ReflectionProperty object.
     *
     * @static
     * @param mixed $object object to be used
     * @param string $fieldName object property name
     * @return mixed given property value
     */
    public static function getPrivateField($object, $fieldName)
    {
        $refId = new \ReflectionProperty($object, $fieldName);
        $refId->setAccessible(true);
        $value = $refId->getValue($object);
        $refId->setAccessible(false);

        return $value;
    } // end: getPrivateField()


    /**
     * Get private / protected static field value using \ReflectionProperty object.
     *
     * @static
     * @param mixed $className class name
     * @param string $fieldName object property name
     * @return mixed given property value
     */
    public static function getStaticField($className, $fieldName)
    {
        $reflectionClass = new \ReflectionClass($className);

        $staticProps = $reflectionClass->getStaticProperties();
        return $staticProps[$fieldName];
    } // end: getStaticField()

    
    public static function setStaticField($className, $fieldName, $value)
    {
        $reflectionClass = new \ReflectionClass($className);        
        $field = $reflectionClass->getProperty($fieldName);
        
        $field->setAccessible(true);
        $field->setValue(null);
        $field->setAccessible(false);
    } // end: getStaticField()

    /**
     * Call private method of the object.
     *
     * @param $object object to be used
     * @param $methodName object method name
     * @return mixed object return value
     */
    public static function callPrivateMethod($object, $methodName)
    {
        $reflectionClass = new \ReflectionClass($object);
        $reflectionMethod = $reflectionClass->getMethod($methodName);
        $reflectionMethod->setAccessible(true);

        $params = array_slice(func_get_args(), 2); //get all the parameters after $methodName
        return $reflectionMethod->invokeArgs($object, $params);
    } // end: callPrivateMethod()
}

/*
 * Check if open short tags are set or not. In newer PHP versions are disabled by default. Can make realy big mess.
 */
if (ini_get('short_open_tag') != "") {
//    echo "WARNING!! short_open_tags are allowed in cli/php.ini!\n";
} else {
//    echo "Short_open_tag are switched off. If some php file is set to output it begins with <? instead of <?php\n ";
}

/**
 * output phpunit version
 */
// echo "Running phpunit " . \PHPUnit_Runner_Version::id() . " load from PHP composer vendor dir.\n\n";

// set error reporting and autoload for composer, library etc.
error_reporting( E_ALL | E_STRICT );
require __DIR__ . '/../bin/autoload.php';

// set multibyte encoding to utf-8 to be sure. Some php configs have not utf-8 by default
mb_internal_encoding('UTF-8');

/**
 * @todo set some constant or service value to indicate to app the phpunit is run. Some session walidators, ACL etc.
 * can work bad w/o it.
 */
