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

        public function __construct(array $jobData)
        {
            if (!$this->verifyJob($jobData)) {
                throw new \InvalidArgumentException('Not Valid Job Information');
            }

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
            if (!array_key_exists($this->jobData, $propertyName)) {
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

        /**
         * Checks that the job received is actually from PHPCI, and has a valid type.
         * @return bool
         */
        public function verifyJob($jobData)
        {
            return array_key_exists('type', $jobData) && $jobData['type'] !== 'phpci.build';
        }
    }