<?php

namespace PHPCI\Worker;

use b8\Config;
use b8\Database;
use b8\Store\Factory;
use Monolog\Logger;
use PHPCI\Builder;
use PHPCI\BuildFactory;
use PHPCI\Helper\JobData;
use PHPCI\Logging\BuildDBLogHandler;
use PHPCI\Model\Build;
/**
 * Class BuildWorker
 * @package PHPCI\Worker
 */
class ChildBuildWorker
{
    /**
     * The logger for builds to use.
     * @var \Monolog\Logger
     */
    protected $logger;

    /**
     * Job Data For The Given Build (Passed From Worker)
     * @var JobData
     */
    protected $jobData = NULL;

    /**
     * Parent Build Worker (InLine Only)
     * @var BuildWorker
     */
    protected $buildWorker = NULL;

    /**
     * @param $host
     * @param $queue
     */
    public function __construct(JobData $jobData, BuildWorker $buildWorker = NULL)
    {
        $this->jobData     = $jobData;
        $this->buildWorker = $buildWorker;
    }

    /**
     * Returns The Job Data Helper Object
     * @return JobData
     */
    public function getJobData()
    {
        return $this->jobData;
    }

    /**
     * @return \PHPCI\Worker\BuildWorker
     */
    public function getBuildWorker()
    {
        return $this->buildWorker;
    }

    /**
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Start the worker.
     */
    public function startWorker()
    {
        $this->logger->addInfo('Received build #' . $this->getJobData()->getBuildId() . ' from Beanstalkd');

        $buildStore = Factory::getStore('Build');

        // If the job comes with config data, reset our config and database connections
        // and then make sure we kill the worker afterwards:
        if ($this->getJobData()->getConfig()) {
            $this->logger->addDebug('Using job-specific config.');
            $currentConfig = Config::getInstance()->getArray();
            $config = new Config($this->getJobData()->getConfig());
            Database::reset($config);
        }

        $build = BuildFactory::getBuildById($this->getJobData()->getBuildId());

        try {
            // Logging relevant to this build should be stored
            // against the build itself.
            $buildDbLog = new BuildDBLogHandler($build, Logger::INFO);
            $this->logger->pushHandler($buildDbLog);

            $builder = new Builder($build, $this->logger);
            $builder->execute();

            // After execution we no longer want to record the information
            // back to this specific build so the handler should be removed.
            $this->logger->popHandler($buildDbLog);
        } catch (\Exception $ex) {
            $build->setStatus(Build::STATUS_FAILED);
            $build->setFinished(new \DateTime());
            $build->setLog($build->getLog() . PHP_EOL . PHP_EOL . $ex->getMessage());
            $buildStore->save($build);
            $build->sendStatusPostback();
        }

        // Reset the config back to how it was prior to running this job:
        if (!empty($currentConfig)) {
            $config = new Config($currentConfig);
            Database::reset($config);
        }
    }
}
