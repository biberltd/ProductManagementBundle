<?php
/**
 * @author		Can Berkol
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com) (C) 2015
 * @license     GPLv3
 *
 * @date        23.12.2015
 */
namespace BiberLtd\Bundle\ProductManagementBundle\Exceptions;

use BiberLtd\Bundle\ExceptionBundle\Services;

class InvalidAttributeSetException extends Services\ExceptionAdapter {
    /**
     * InvalidAttributeSetException constructor.
     *
     * @param string                                                             $kernel
     * @param string                                                             $msg
     * @param string                                                             $code
     * @param \BiberLtd\Bundle\ProductManagementBundle\Exceptions\Exception|null $previous
     */
    public function __construct($kernel, string $msg = "", string $code = 'PMB0001', Exception $previous = null) {
        $numeriCode = ord($code[0]).ord($code[1]).ord($code[2]).substr($code, 2, 3);
        parent::__construct(
            $kernel,
            $code.' :: Invalid Attribute Set'.PHP_EOL.'$set parameter must contain an array with "attribute" and "sortorder" keys.',
            $numeriCode,
            $previous);
     }
}