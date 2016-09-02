<?php
    /**
     * Created by PhpStorm.
     * User: fenikkusu
     * Date: 9/2/16
     * Time: 6:46 PM
     */
    namespace Tests\PHPCI\Helper;

    use PHPCI\Helper\JobData;
    use PHPUnit_Framework_TestCase;

    class JobDataTest extends PHPUnit_Framework_TestCase {

        /**
         * @dataProvider dpConstruct
         * @param array $dataSet
         * @param bool $isValid
         */
        public function testConstruct($dataSet, $isValid) {
            if (!$isValid) {
                $this->setExpectedException('\PHPCI\Exceptions\InvalidJobData');
            }

            new JobData($dataSet);
        }

        public function testSetJobData() {
            $testSubject = new JobData(['type' => 'phpci.build']);

            $reflectionProperty = new \ReflectionProperty('\PHPCI\Helper\JobData', 'jobData');
            $reflectionProperty->setAccessible(TRUE);

            $this->assertSame($testSubject->getDefaults(), $reflectionProperty->getValue($testSubject), 'Job Data Does Not Have Defaults');
        }

        public function testHelpers() {
            $testSubject = new JobData(['type' => 'phpci.build', 'build_id' => -1000, 'config' => 'something']);

            $this->assertEquals(-1000, $testSubject->getBuildId());
            $this->assertEquals('something', $testSubject->getConfig());
        }

        public function testGetBuild() {
            $mockBuild = $this->getMockBuilder('\PHPCI\Model\Build')
                ->disableOriginalConstructor()
                ->getMock();

            /** @var \PHPCI\Helper\JobData|\PHPUnit_Framework_MockObject_MockObject $testSubject */
            $testSubject = $this->getMockBuilder('\PHPCI\Helper\JobData')
                ->setConstructorArgs([['type' => 'phpci.build']])
                ->setMethods(['loadBuild'])
                ->getMock();

            $testSubject->expects($this->once())
                ->method('loadBuild')
                ->willReturn($mockBuild);

            $this->assertSame($mockBuild, $testSubject->getBuild());
            $this->assertSame($mockBuild, $testSubject->getBuild());
        }

        /**
         * @dataProvider dpGetProperty
         * @param string $propertyName
         * @param mixed $propertyValue
         */
        public function testGetProperty($propertyName, $propertyValue) {
            $testData = ['type' => 'phpci.build'];

            if ($propertyValue === NULL) {
                $this->setExpectedException('\InvalidArgumentException');
            } else {
                $testData[$propertyName] = $propertyValue;
            }

            $testSubject = new JobData($testData);
            $this->assertEquals($propertyValue, $testSubject->getProperty($propertyName));
        }

        public function dpGetProperty() {
            return [
                ['something', 'else'],
                ['another'  , 'value'],
                ['exception', NULL]
            ];
        }

        public function dpConstruct() {
            return [
                [
                    [
                        'build_id' => -1000
                    ],
                    FALSE
                ],
                [
                    [
                        'build_id' => -1000,
                        'type'     => 'phpci.something'
                    ],
                    FALSE
                ],
                [
                    [
                        'build_id' => -1000,
                        'type'     => 'phpci.build'
                    ],
                    TRUE
                ]
            ];
        }
    }
