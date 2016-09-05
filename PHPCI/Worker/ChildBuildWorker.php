<?php

namespace PHPCI\Worker;

use b8\Config;
use b8\Database;
use b8\Store\Factory;
use Monolog\Logger;
use PHPCI\Builder;
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
     * Constructor
     *
     * @param JobData $jobData
     */
    public function __construct(JobData $jobData)
    {
        $this->jobData     = $jobData;
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
     * @param Logger $logger
     * @return $this
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
        $currentConfig = NULL;
        $this->logger->addInfo('Received build #' . $this->getJobData()->getBuildId());

        // If the job comes with config data, reset our config and database connections
        // and then make sure we kill the worker afterwards:
        if ($this->getJobData()->getConfig()) {
            $this->logger->addDebug('Using job-specific config.');
            $currentConfig = $this->loadCurrentConfig();
            $this->resetDatabase($this->getJobData()->getConfig());
        }

        $build = $this->getJobData()->getBuild(); //Letting Parent Handle Any Errors

        try {
            // Logging relevant to this build should be stored
            // against the build itself.
            $buildDbLog = $this->loadBuildLogger($build);

            $this->logger->pushHandler($buildDbLog);

            $this->loadBuilder($build)->execute();

            // After execution we no longer want to record the information
            // back to this specific build so the handler should be removed.
            $this->logger->popHandler($buildDbLog);
        } catch (\Exception $ex) {
            $build->setStatus(Build::STATUS_FAILED);
            $build->setFinished(new \DateTime());
            $build->setLog($build->getLog() . PHP_EOL . PHP_EOL . $ex->getMessage());
            $this->loadBuildStore()->save($build);
            $build->sendStatusPostback();
        }

        // Reset the config back to how it was prior to running this job:
        if (!empty($currentConfig)) {
            $this->resetDatabase($currentConfig);
        }
    }

    protected function loadBuildLogger(Build $buildData) {
        return new BuildDBLogHandler($buildData, Logger::INFO);
    }

    protected function loadBuilder(Build $buildData) {
        return new Builder($buildData, $this->logger);
    }

    protected function resetDatabase(array $configData) {
        $config = new Config($configData);
        Database::reset($config);

        return $this;
    }

    protected function loadCurrentConfig() {
        return Config::getInstance()->getArray();
    }

    protected function loadBuildStore() {
        return Factory::getStore('Build');
    }
}
