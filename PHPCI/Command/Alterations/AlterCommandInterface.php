<?php
    /**
     * Created by PhpStorm.
     * User: fenikkusu
     * Date: 8/9/16
     * Time: 10:52 PM
     */

    namespace PHPCI\Command\Alterations;

    use PHPCI\Helper\BaseCommandExecutor;

    interface AlterCommandInterface {
        /** @return BaseCommandExecutor */
        public function getCommand();
        public function setCommand(BaseCommandExecutor $parentCommand);
        public function setArguments(array $args);
        /** @return array */
        public function getArguments();

        /** @return AlterCommandInterface */
        public function runAlteration();
    }