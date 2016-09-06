<?php
    /**
     * Created by PhpStorm.
     * User: fenikkusu
     * Date: 9/5/16
     * Time: 11:09 PM
     */
    namespace Tests\PHPCI\PHPCI\Command;

    class BuildCommandTest extends \PHPUnit_Framework_TestCase {
        /** @var  \Monolog\Logger */
        protected $mockLogger;

        /** @var  \PHPCI\Command\BuildCommand | \PHPUnit_Framework_MockObject_MockObject */
        protected $testSubject;

        public function setUp()
        {
            $this->mockLogger = $this->getMockBuilder('\Monolog\Logger')
                ->disableOriginalConstructor()
                ->setMethods(['addInfo', 'pushHandler'])
                ->getMock();

            $this->mockLogger->method('pushHandler')
                ->with($this->isInstanceOf('PHPCI\Logging\OutputLogHandler'));

            $this->testSubject = $this->getMockBuilder('\PHPCI\Command\BuildCommand')
                ->setConstructorArgs([$this->mockLogger])
                ->setMethods(['buildJobData', 'buildWorker'])
                ->getMock();
        }

        /**
         * @dataProvider dpExecute
         */
        public function testExecute($buildId, $options, $throwsException = FALSE) {
            $mockWorker = $this->getMockBuilder('\PHPCI\Worker\ChildBuildWorker')
                ->disableOriginalConstructor()
                ->getMock();

            $mockInput = $this->getMockBuilder('Symfony\Component\Console\Input\ArgvInput')
                ->disableOriginalConstructor()
                ->setMethods(['getArgument', 'getOption', 'hasOption'])
                ->getMock();

            $mockOutput = $this->getMockBuilder('Symfony\Component\Console\Output\ConsoleOutput')
                ->disableOriginalConstructor()
                ->getMock();

            $mockInput->expects($this->once())
                ->method('getArgument')
                ->with('buildId')
                ->willReturn($buildId);

            $mockInput->method('hasOption')
                ->willReturnCallback(function($optionName) use ($options) {
                    return isset($options[$optionName]);
                });

            $mockInput->method('getOption')
                ->willReturnCallback(function($optionName) use ($options) {
                    if (!isset($options[$optionName])) {
                        $this->fail('Failed To Check hasOption For ' . $optionName);
                    }

                    return $options[$optionName];
                });

            $mockJobData = $this->getMockBuilder('\PHPCI\Helper\JobData')
                ->disableOriginalConstructor()
                ->getMock();

            $this->testSubject->method('buildWorker')
                ->with($this->isInstanceOf('\PHPCI\Helper\JobData'))
                ->willReturn($mockWorker);

            $this->testSubject->method('buildJobData')
                ->with($this->isType('array'))
                ->willReturn($mockJobData);

            if ($throwsException) {
                $this->setExpectedException('\InvalidArgumentException');
            }

            $reflectionMethod = new \ReflectionMethod('\PHPCI\Command\BuildCommand', 'execute');
            $reflectionMethod->setAccessible(TRUE);

            $reflectionMethod->invoke($this->testSubject, $mockInput, $mockOutput);
        }

        public function dpExecute()
        {
            return [
                [
                    1000,
                    []
                ],
                [
                    0,
                    [],
                    TRUE
                ],
                [
                    0,
                    ['buildConfig' => \json_encode(['build_id' => 1000])]
                ],
                [
                    1000,
                    ['buildConfig' => \json_encode(['type' => 'something'])]
                ],
                [
                    1000,
                    ['verbose' => TRUE]
                ],
                [
                    1000,
                    ['debug' => TRUE]
                ]
            ];
        }
    }
