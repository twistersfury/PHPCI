<?php
    /**
     * Created by PhpStorm.
     * User: fenikkusu
     * Date: 8/10/16
     * Time: 12:52 PM
     */

    namespace PHPCI\Plugin;

    class PhpSelector extends AbstractPlugin {
        const C_INI_DIRECTORY = 'PHPCI_INI_DIRECTORY_OLD';
        const C_PATH          = 'PHPCI_PATH_OLD';
        const C_NOT_SET       = 'PHPCI_NOT_SET';

        private $environmentSettings = [];

        public function backupOriginals() {
            if (($currentIni = $this->getEnv(static::C_INI_DIRECTORY)) === NULL) {
                $this->backupIni();
            }

            if (($currentPath = $this->getEnv(static::C_PATH)) === NULL) {
                $this->backupPath();
            }

            return $this;
        }

        protected function backupIni() {
            $currentIni = $this->getEnv('PHPRC');
            if ($currentIni === NULL) {
                $currentIni = static::C_NOT_SET;
            }

            return $this->addEnvironment(static::C_INI_DIRECTORY, $currentIni);
        }

        protected function backupPath() {
            return $this->addEnvironment(static::C_PATH, $this->getEnv('PATH'));
        }

        public function addEnvironment($environmentName, $environmentValue) {
            $this->environmentSettings[$environmentName] = $environmentValue;

            return $this;
        }

        public function loadOriginals() {
            $originalIni = $this->getEnv(static::C_INI_DIRECTORY);
            if ($originalIni === static::C_NOT_SET) {
                $originalIni = '';
            }

            return $this->addEnvironment('PHPRC', $originalIni)
                ->addEnvironment('PATH', $this->getEnv(static::C_PATH));
        }

        public function prepareIni() {
            if (($iniDirectory = $this->getOption('ini_directory')) !== NULL) {
                $this->addEnvironment('PHPRC', $iniDirectory);
            }

            return $this;
        }

        public function preparePath() {
            if (($pathDirectory = $this->getOption('php_directory')) !== NULL) {
                $this->addEnvironment('PATH', $pathDirectory . ':' . $this->getEnv(static::C_PATH));
            }

            return $this;
        }

        public function getEnv($environmentName) {
            return getenv($environmentName);
        }

        public function executeEnvironments() {
            $phpEnv = new Env($this->getBuilder(), $this->getModel(), $this->environmentSettings);
            $phpEnv->execute();

            return $this;
        }

        public function execute() {
            return $this->backupOriginals()
                ->loadOriginals()
                ->prepareIni()
                ->preparePath()
                ->executeEnvironments();
        }
    }