<?php
    /**
     * Created by PhpStorm.
     * User: fenikkusu
     * Date: 9/7/16
     * Time: 11:16 PM
     */

    namespace PHPCI\Exceptions;

    class BuildAlreadyRan extends \RuntimeException {
        public function __construct($message, $code = 1024, \Exception $previous = NULL) { parent::__construct($message, $code, $previous); }
    }