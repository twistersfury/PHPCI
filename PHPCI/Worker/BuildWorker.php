<?php

namespace PHPCI\Worker;

use Monolog\Logger;
use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;
use PHPCI\Exceptions\BuildAlreadyRan;
use PHPCI\Helper\BaseCommandExecutor;
use PHPCI\Helper\CommandExecutor;
use PHPCI\Helper\JobData;
use PHPCI\Logging\BuildLogger;
use PHPCI\Model\Build;

/**
 * Class BuildWorker
 * @package PHPCI\Worker
 */
class BuildWorker
{
    /**
     * If this variable changes to false, the worker will stop after the current build.
     * @var bool
     */
    protected $run = true;

    /**
     * The maximum number of jobs this worker should run before exiting.
     * Use -1 for no limit.
     * @var int
     */
    protected $maxJobs = -1;

    /**
     * The logger for builds to use.
     * @var \Monolog\Logger
     */
    protected $logger;

    /**
     * beanstalkd host
     * @var string
     */
    protected $host;

    /**
     * beanstalkd queue to watch
     * @var string
     */
    protected $queue;

    /**
     * @var \Pheanstalk\Pheanstalk
     */
    protected $pheanstalk;

    /**
     * @var int
     */
    protected $totalJobs = 0;

    /** @var \PHPCI\Helper\BaseCommandExecutor */
    private $commandExecutor = NULL;

    /**
     * @param $host
     * @param $queue
     */
    public function __construct($host, $queue)
    {
        $this->host = $host;
        $this->queue = $queue;
        $this->pheanstalk = new Pheanstalk($this->host);
    }

    /**
     * @param int $maxJobs
     */
    public function setMaxJobs($maxJobs = -1)
    {
        $this->maxJobs = $maxJobs;
    }

    /**
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Start the worker.
     */
    public function startWorker()
    {
        $this->pheanstalk->watch($this->queue);
        $this->pheanstalk->ignore('default');

        while ($this->run) {
            // Get a job from the queue:
            /** @var Job $job */
            $job = $this->pheanstalk->reserve();

            $this->checkJobLimit();

            $deleteJob = true;

            try {
                $runMethod = 'Local';
                if ($this->canRunChild($job)) {
                    $runMethod = 'Child';
                }
                $this->{'run' . $runMethod}($job);
            } catch (\PDOException $ex) {
                // If we've caught a PDO Exception, it is probably not the fault of the build, but of a failed
                // connection or similar. Release the job and kill the worker.
                $this->run = false;
                $deleteJob = false;

                $this->pheanstalk->release($job);
            } catch (\InvalidArgumentException $ex) {
                //Meh
            } catch (BuildAlreadyRan $ex) {
                //Meh
            }

            // Delete the job when we're done:
            if ($deleteJob) {
                $this->pheanstalk->delete($job);
            }
        }
    }

    public function canRunChild(Job $job)
    {
        //TODO: Implement Run Child
        return TRUE;
    }

    public function runChild(Job $job)
    {
        $jobData = $this->loadJobData($job);

        $this->logger->addInfo('Starting Child Build');

        $buildCommand   = APPLICATION_PATH . 'console phpci:build --buildConfig="%s" %d';
        $buildArguments = [
            addslashes(\json_encode($jobData->toArray())),
            $jobData->getBuildId()
        ];

        $jobConfig = $jobData->getBuild()->getConfig();
        if (isset($jobConfig['child']['environment'])) {
            foreach($jobConfig['child']['environment'] as $envVar => $envValue) {
                $buildCommand = $envValue . '="%s" ' . $buildCommand;
                array_unshift($buildArguments, $envValue);
            }
        }

        array_unshift($buildArguments, $buildCommand);

        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $returnStatus = $this->getCommandExecutor($jobData->getBuild())->executeCommand(
            $buildArguments
        );

        $this->logger->addInfo('Child Build Complete');

        if ($returnStatus !== 0) {
            throw $this->buildReturnException($returnStatus);
        }
    }

    protected function buildReturnException($returnStatus)
    {
        $exceptionName = 'Exception';
        switch($returnStatus) {
            case 255: //TODO: Huh?
                $exceptionName = '\PHPCI\Exceptions\BuildAlreadyRan';
                break;
        }

        return new $exceptionName($this->getCommandExecutor()->getLastError(), $returnStatus);
    }

    public function getCommandExecutor(Build $buildData = NULL)
    {
        if ($this->commandExecutor === NULL) {
            $className = 'UnixCommandExecutor';
            if (IS_WIN) {
                $className = 'WindowsCommandExecutor';
            }

            $className = '\PHPCI\Helper\\' . $className;

            /** @var BaseCommandExecutor $commandExecutor */
            $commandExecutor = new $className(new BuildLogger($this->logger, $buildData), APPLICATION_PATH);
            $commandExecutor->setReturnStatus(TRUE);

            $this->setCommandExecutor($commandExecutor);
        }

        return $this->commandExecutor;
    }

    public function setCommandExecutor(CommandExecutor $command)
    {
        $this->commandExecutor = $command;

        return $this;
    }

    /**
     * @param $job
     *
     * @return $this
     */
    public function runLocal(Job $job)
    {
        $jobData = $this->loadJobData($job);

        $childWorker = new ChildBuildWorker($jobData, $this);
        $childWorker->setLogger($this->logger)->startWorker();

        return $this;
    }

    protected function loadJobData(Job $job) {
        return new JobData(json_decode($job->getData(), TRUE));
    }

    /**
     * Stops the worker after the current build.
     */
    public function stopWorker()
    {
        $this->run = false;
    }

    /**
     * Checks if this worker has done the amount of jobs it is allowed to do, and if so tells it to stop
     * after this job completes.
     */
    protected function checkJobLimit()
    {
        // Make sure we don't run more than maxJobs jobs on this worker:
        $this->totalJobs++;

        if ($this->maxJobs != -1 && $this->maxJobs <= $this->totalJobs) {
            $this->stopWorker();
        }
    }
}
