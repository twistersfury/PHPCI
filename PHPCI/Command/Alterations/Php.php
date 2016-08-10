<?php
    /**
     * Created by PhpStorm.
     * User: fenikkusu
     * Date: 8/9/16
     * Time: 10:55 PM
     */

    namespace PHPCI\Command\Alterations;

    class Php extends AbstractAlteration
    {
        public function runAlteration() {
            if ($this->getArguments() === NULL) {
                throw new \InvalidArgumentException('Arguments Not Set For Php Alter Command');
            } else if ($this->getCommand() === NULL) {
                throw new \InvalidArgumentException('Command Not Set For Php Alter Command');
            }

            
        }
    }