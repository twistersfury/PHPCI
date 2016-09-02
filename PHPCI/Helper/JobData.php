<?php
    namespace PHPCI\Helper;

    use PHPCI\BuildFactory;
    use PHPCI\Exceptions\InvalidJobData;

    /**
     * Class Used To Wrap Job Data From Beanstalk Queue/Manual Build Run.
     *
     * Class JobData
     * @author Phoenix Osiris <phoenix@twistersfury.com>
     *
     * @package PHPCI\Helper
     */
    class JobData
    {

        /**
         * @var array
         */
        protected $jobData = [];

        /**
         * @var \PHPCI\Model\Build
         */
        private $buildInstance = NULL;

        /**
         * JobData constructor.
         *
         * @param array $jobData
         */
        public function __construct(array $jobData)
        {
            if (!$this->verifyJob($jobData)) {
                throw new InvalidJobData('Not Valid Job Information');
            }

            $this->setJobData($jobData);
        }

        /**
         * Sets Internal Job Data
         *
         * @param array $jobData
         *
         * @return $this
         */
        public function setJobData(array $jobData)
        {
            $this->jobData = array_merge($this->getDefaults(), $jobData);

            return $this;
        }

        /**
         * JobData Defaults
         *
         * @return array
         */
        public function getDefaults()
        {
            return [
                'build_id' => NULL,
                'config'   => NULL,
                'type'     => 'phpci.build'
            ];
        }

        /**
         * Returns Value From Job Data
         *
         * @param string $propertyName
         *
         * @return mixed
         */
        public function getProperty($propertyName)
        {
            if (!array_key_exists($propertyName, $this->jobData)) {
                throw new \InvalidArgumentException('Property Not Defined: ' . $propertyName);
            }

            return $this->jobData[$propertyName];
        }

        /**
         * Checks that the job received is actually from PHPCI, and has a valid type.
         *
         * @return bool
         */
        public function verifyJob($jobData)
        {
            return array_key_exists('type', $jobData) && $jobData['type'] === 'phpci.build';
        }

        /**
         * Gets Build Model Instance
         *
         * @return \PHPCI\Model\Build
         */
        public function getBuild()
        {
            if ($this->buildInstance === NULL) {
                $this->buildInstance = $this->loadBuild();
            }

            return $this->buildInstance;
        }

        /**
         * Helper Method To Access Build ID
         * @return int
         */
        public function getBuildId()
        {
            return $this->getProperty('build_id');
        }

        /**
         * Helper Method To Access Config Settings
         * @return array
         */
        public function getConfig()
        {
            return $this->getProperty('config');
        }

        /**
         * Helper Method For Testing (Method Overridden During Testing)
         *
         * @access protected
         * @return \PHPCI\Model\Build
         */
        protected function loadBuild()
        {
            return BuildFactory::getBuildById($this->getBuildId());
        }
    }