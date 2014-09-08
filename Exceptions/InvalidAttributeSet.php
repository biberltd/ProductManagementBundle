<?php
/**
 * @name        InvalidAttributeSetException
 * @package		BiberLtd\Bundle\ProductManagementBundle\Exceptions
 *
 * @author		Can Berkol
 * @version     1.0.0
 * @date        17.06.2014
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com)
 * @license     GPL v3.0
 *
 * @description Exception to $set collection in product attribute related functions.
 *
 */
namespace BiberLtd\Bundle\ProductManagementBundle\Exceptions;

use BiberLtd\Bundle\ExceptionBundle\Services;

class InvalidAttributeSetException extends Services\ExceptionAdapter {
    public function __construct($kernel, $msg = "", $code = 'PMB0001', Exception $previous = null) {
        $numeriCode = ord($code[0]).ord($code[1]).ord($code[2]).substr($code, 2, 3);
        parent::__construct(
            $kernel,
            $code.' :: Invalid Attribute Set'.PHP_EOL.'$set parameter must contain an array with "attribute" and "sortorder" keys.',
            $numeriCode,
            $previous);
     }
}
/**
 * Change Log:
 * **************************************
 * v1.0.0                      Can Berkol
 * 17.06.2014
 * **************************************
 * A __construct()
 */