<?php
    /**
     * Created by PhpStorm.
     * User: fenikkusu
     * Date: 8/10/16
     * Time: 3:46 PM
     */
    namespace Tests\PHPCI\Plugin;

    class AbstractPluginTest extends  \PHPUnit_Framework_TestCase {
        public function testGetOption() {
            $mockBuilder = $this->getMockBuilder('\PHPCI\Builder')
                ->disableOriginalConstructor()
                ->getMock();

            $mockBuild = $this->getMockBuilder('\PHPCI\Model\Build')
                ->disableOriginalConstructor()
                ->getMock();

            /** @var \PHPCI\Plugin\AbstractPlugin | \PHPUnit_Framework_MockObject_MockBuilder $testPlugin */
            $testPlugin = $this->getMockBuilder('\PHPCI\Plugin\AbstractPlugin')
                ->setConstructorArgs(
                    [
                        $mockBuilder,
                        $mockBuild,
                        [
                            'some_option' => 'exists'
                        ]
                    ]
                )
                ->getMockForAbstractClass();

            $this->assertEquals('exists', $testPlugin->getOption('some_option', 'does not exist'));
            $this->assertEquals('does not exist', $testPlugin->getOption('another_option', 'does not exist'));
        }
    }
