<?php

namespace PHPCI\Worker;

use Monolog\Logger;
use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;
use PHPCI\Helper\JobData;

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

            try {
                $jobData = new JobData(json_decode($job->getData(), TRUE));

                $childWorker = new ChildBuildWorker($jobData, $this);
                $childWorker->setLogger($this->logger)->startWorker();
            } catch (\PDOException $ex) {
                // If we've caught a PDO Exception, it is probably not the fault of the build, but of a failed
                // connection or similar. Release the job and kill the worker.
                $this->run = false;
                $this->pheanstalk->release($job);
            } catch (\InvalidArgumentException $ex) {
                //Likely Due To
                $this->pheanstalk->delete($job);
                continue;
            }

            // Delete the job when we're done:
            $this->pheanstalk->delete($job);
        }
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
