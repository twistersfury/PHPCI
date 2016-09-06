<?php
    /**
     * PHPCI - Continuous Integration for PHP
     *
     * @copyright    Copyright 2015, Block 8 Limited.
     * @license      https://github.com/Block8/PHPCI/blob/master/LICENSE.md
     * @link         https://www.phptesting.org/
     */

    namespace PHPCI\Command;

    use Monolog\Logger;
    use PHPCI\Helper\JobData;
    use PHPCI\Logging\OutputLogHandler;
    use PHPCI\Worker\ChildBuildWorker;
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Output\OutputInterface;


    /**
     * Build Command - Starts the ChildBuildWorker, which runs an individual build.
     * @author       Phoenix Osiris <phoenix@twistersfury.com>
     * @package      PHPCI
     * @subpackage   Console
     */
    class BuildCommand extends Command
    {
        /**
         * @var OutputInterface
         */
        protected $output;

        /**
         * @var InputInterface
         */
        protected $inputInterface;

        /**
         * @var Logger
         */
        protected $logger;

        /**
         * @param \Monolog\Logger $logger
         * @param string $name
         */
        public function __construct(Logger $logger, $name = null)
        {
            parent::__construct($name);
            $this->logger = $logger;
        }

        protected function configure()
        {
            $this
                ->setName('phpci:build')
                ->setDescription('Runs the PHPCI child build worker.')
                ->addArgument('buildId', NULL, InputOption::VALUE_REQUIRED, 'Build ID To Run')
                ->addOption('debug', null, null, 'Run PHPCI in Debug Mode')
                ->addOption('buildConfig', 'c', InputOption::VALUE_OPTIONAL, 'JSON Build Configuration to Run.');
        }

        protected function execute(InputInterface $input, OutputInterface $output)
        {
            $this->inputInterface = $input;
            $this->output         = $output;

            // For verbose mode we want to output all informational and above
            // messages to the symphony output interface.
            if ($input->hasOption('verbose') && $input->getOption('verbose')) {
                $this->logger->pushHandler(
                    new OutputLogHandler($this->output, Logger::INFO)
                );
            }

            // Allow PHPCI to run in "debug mode"
            if ($input->hasOption('debug') && $input->getOption('debug')) {
                $output->writeln('<comment>Debug mode enabled.</comment>');
                define('PHPCI_DEBUG_MODE', true);
            }

            $config = $this->buildConfig();

            if (empty($config['build_id']) || !is_numeric($config['build_id']) || empty($config['type'])) {
                throw new \InvalidArgumentException('Improperly Configured Build Request.');
            }

            $jobData = $this->buildJobData($config);

            $this->buildWorker($jobData)->startWorker();
        }

        protected function buildWorker(JobData $jobData) {
            $worker = new ChildBuildWorker($jobData);
            $worker->setLogger($this->logger);

            return $worker;
        }

        protected function buildConfig()
        {
            $buildConfig = array_merge(
                [
                    'build_id' => $this->inputInterface->getArgument('buildId'),
                    'type'     => 'phpci.build',
                ],
                $this->parseConfig()
            );

            return $buildConfig;
        }

        protected function parseConfig()
        {
            if (!$this->inputInterface->hasOption('buildConfig')) {
                return [];
            }

            $configData = $this->inputInterface->getOption('buildConfig');
            $this->logger->addInfo('JSON Configuration Passed: ' . $configData);

            $configData = \json_decode($configData, TRUE);
            if (!$configData) {
                throw new \InvalidArgumentException('Improperly Formed JSON Configuration');
            }

            return $configData;
        }

        protected function buildJobData(array $configData)
        {
            return new JobData($configData);
        }
    }
