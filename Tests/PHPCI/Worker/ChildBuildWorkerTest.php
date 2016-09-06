<?php
    /**
     * Created by PhpStorm.
     * User: fenikkusu
     * Date: 9/4/16
     * Time: 11:43 PM
     */
    namespace Tests\PHPCI\PHPCI\Worker;

    use Monolog\Logger;
    use PHPCI\Model\Build;

    class ChildBuildWorkerTest extends \PHPUnit_Framework_TestCase {
        /** @var Logger|\PHPUnit_Framework_MockObject_MockObject */
        private $mockLogger = NULL;

        /** @var \PHPCI\Model\Build|\PHPUnit_Framework_MockObject_MockObject */
        private $mockBuild  = NULL;

        /** @var \PHPCI\Helper\JobData|\PHPUnit_Framework_MockObject_MockObject */
        private $mockJobData = NULL;

        /** @var \PHPCI\Worker\ChildBuildWorker|\PHPUnit_Framework_MockObject_MockObject */
        private $testSubject = NULL;

        /** @var \PHPCI\Builder|\PHPUnit_Framework_MockObject_MockObject */
        private $mockBuilder = NULL;

        public function setUp() {
            $this->mockLogger = $this->getMockBuilder('\Monolog\Logger')
                ->disableOriginalConstructor()
                ->setMethods(['addInfo', 'pushHandler', 'popHandler', 'addDebug'])
                ->getMock();

            $this->mockLogger->method('addInfo')
                ->with($this->isType('string'));

            $this->mockLogger->method('addDebug')
                ->with($this->isType('string'));

            $this->mockBuild = $this->getMockBuilder('\PHPCI\Model\Build')
                ->disableOriginalConstructor()
                ->setMethods(['setStatus', 'setLog', 'getStatus'])
                ->getMock();

            $this->mockBuild->method('getStatus')
                  ->willReturn(Build::STATUS_NEW);

            $this->mockJobData = $this->getMockBuilder('\PHPCI\Helper\JobData')
                ->disableOriginalConstructor()
                ->setMethods(['getBuild', 'getBuildId', 'getConfig'])
                ->getMock();

            $this->mockJobData->expects($this->once())
                ->method('getBuild')
                ->willReturn($this->mockBuild);

            $this->mockJobData->method('getBuildId')
                ->willReturn(-1000);

            $mockBuildLogger = $this->getMockBuilder('\PHPCI\Logging\BuildDBLogHandler')
                ->disableOriginalConstructor()
                ->getMock();

            $this->mockBuilder = $this->getMockBuilder('PHPCI\Builder')
                ->disableOriginalConstructor()
                ->setMethods(['execute'])
                ->getMock();

            $this->testSubject = $this->getMockBuilder('\PHPCI\Worker\ChildBuildWorker')
                ->setConstructorArgs([$this->mockJobData])
                ->setMethods(['loadBuildLogger', 'loadBuilder', 'resetDatabase', 'loadCurrentConfig', 'loadBuildStore'])
                ->getMock();

            $this->testSubject->expects($this->once())
                ->method('loadBuildLogger')
                ->willReturn($mockBuildLogger);

            $this->testSubject->expects($this->once())
                ->method('loadBuilder')
                ->willReturn($this->mockBuilder);

            $this->testSubject->setLogger($this->mockLogger);
        }

        public function testStartWorkerWithoutConfig() {
            $this->mockJobData->expects($this->once())
                ->method('getConfig')
                ->willReturn(NULL);

            $this->mockBuilder->expects($this->once())
                ->method('execute');

            $this->testSubject->expects($this->never())
                ->method('resetDatabase');

            $this->testSubject->expects($this->never())
                ->method('loadCurrentConfig');

            $this->testSubject->startWorker();
        }

        public function testStartWorkerWithConfig() {
            $originalConfig = ['thing' => 'some'];
            $configData     = ['some' => 'thing'];

            $this->mockJobData->expects($this->exactly(2))
                              ->method('getConfig')
                              ->willReturn($configData);

            $this->mockBuilder->expects($this->once())
                              ->method('execute');

            $this->testSubject->expects($this->exactly(2))
                ->method('resetDatabase')
                ->withConsecutive(
                    [$configData],
                    [$originalConfig]
                )
                ->willReturnSelf();

            $this->testSubject->expects($this->once())
                ->method('loadCurrentConfig')
                ->willReturn($originalConfig);

            $this->testSubject->startWorker();
        }

        public function testStartWorkerWithException() {
            $stubException = new \Exception('Some Error Occurred');

            $mockStore = $this->getMockBuilder('\PHPCI\Store\BuildStore')
                ->disableOriginalConstructor()
                ->setMethods(['save'])
                ->getMock();

            $mockStore->expects($this->once())
                ->method('save')
                ->with($this->mockBuild);

            $this->mockBuild->expects($this->once())
                ->method('setStatus')
                ->with(Build::STATUS_FAILED);

            $this->mockBuild->expects($this->once())
                ->method('setLog')
                ->with($this->stringEndsWith('Some Error Occurred'));

            $this->mockJobData->expects($this->once())
                              ->method('getConfig')
                              ->willReturn(NULL);

            $this->mockBuilder->expects($this->once())
                              ->method('execute')
                              ->willThrowException($stubException);

            $this->testSubject->expects($this->never())
                              ->method('resetDatabase');

            $this->testSubject->expects($this->never())
                              ->method('loadCurrentConfig');

            $this->testSubject->expects($this->once())
                ->method('loadBuildStore')
                ->willReturn($mockStore);

            $this->testSubject->startWorker();
        }
    }
