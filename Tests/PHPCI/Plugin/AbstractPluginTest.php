<?php
    /**
     * Created by PhpStorm.
     * User: fenikkusu
     * Date: 8/10/16
     * Time: 3:46 PM
     */
    namespace Tests\PHPCI\Plugin;

    class AbstractPluginTest extends  \PHPUnit_Framework_TestCase {

        /** @var \PHPCI\Plugin\AbstractPlugin|\PHPUnit_Framework_MockObject_MockBuilder $testPlugin */
        private $testPlugin = NULL;

        public function setUp() {
            $mockBuilder = $this->getMockBuilder('\PHPCI\Builder')
                                ->disableOriginalConstructor()
                                ->getMock();

            $mockBuild = $this->getMockBuilder('\PHPCI\Model\Build')
                              ->disableOriginalConstructor()
                              ->getMock();

            $this->testPlugin = $this->getMockBuilder('\PHPCI\Plugin\AbstractPlugin')
                               ->disableOriginalConstructor()
                               ->getMockForAbstractClass();

            $this->assertSame($this->testPlugin, $this->testPlugin->setModel($mockBuild));
            $this->assertSame($this->testPlugin, $this->testPlugin->setBuilder($mockBuilder));
            $this->assertSame($this->testPlugin, $this->testPlugin->setOptions(['some_option' => 'exists']));
        }

        public function testGetOption() {
            $this->assertEquals('exists'        , $this->testPlugin->getOption('some_option', 'does not exist'));
            $this->assertEquals('does not exist', $this->testPlugin->getOption('another_option', 'does not exist'));
        }
    }
