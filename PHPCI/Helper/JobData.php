<?php
    /**
     * Created by PhpStorm.
     * User: fenikkusu
     * Date: 8/30/16
     * Time: 11:21 PM
     */

    namespace PHPCI\Helper;

    class JobData
    {

        /**
         * Specific Job Data
         * @var array
         */
        protected $jobData = [];

        public function __construct($jobData)
        {
            $this->setJobData($jobData);
        }

        public function setJobData(array $jobData)
        {
            $this->jobData = array_merge($this->getDefaults(), $jobData);

            return $this;
        }

        public function getDefaults()
        {
            return [
                'build_id' => NULL,
                'config'   => NULL
            ];
        }

        public function getProperty($propertyName)
        {
            if (!array_key_exists($this->jobData, $propertyName) || empty($this->jobData[$propertyName])) {
                throw new \InvalidArgumentException('Property Not Defined: ' . $propertyName);
            }

            return $this->jobData[$propertyName];
        }

        public function getBuildId()
        {
            return $this->getProperty('build_id');
        }

        public function getConfig()
        {
            return $this->getProperty('config');
        }
    }