<?php
    /**
     * Created by PhpStorm.
     * User: fenikkusu
     * Date: 8/9/16
     * Time: 11:03 PM
     */

    namespace PHPCI\Command\Alterations;

    use PHPCI\Helper\BaseCommandExecutor;

    abstract class AbstractAlteration implements AlterCommandInterface
    {
        private $baseCommand      = NULL;
        private $commandArguments = NULL;

        public function setCommand(BaseCommandExecutor $parentCommand)
        {
            $this->baseCommand = $parentCommand;

            return $this;
        }

        public function setArguments(array $args)
        {
            $this->commandArguments = $args;

            return $this;
        }

        public function getArguments()
        {
            return $this->commandArguments;
        }

        public function getCommand()
        {
            return $this->baseCommand;
        }
    }