<?php
    /**
     * Created by PhpStorm.
     * User: fenikkusu
     * Date: 8/10/16
     * Time: 10:36 PM
     */
    namespace Tests\PHPCI\Plugin;

    use PHPCI\Plugin\PhpSelector;

    class PhpSelectorTest extends \PHPUnit_Framework_TestCase {
        public function testBackupOriginalSet() {
            /** @var \PHPCI\Plugin\PhpSelector|\PHPUnit_Framework_MockObject_MockObject $testPlugin */
            $testPlugin = $this->getMockBuilder('\PHPCI\Plugin\PhpSelector')
                               ->disableOriginalConstructor()
                               ->setMethods(['getEnv', 'backupIni', 'backupPath'])
                               ->getMock();


            $testPlugin->expects($this->exactly(2))
                       ->method('getEnv')
                       ->withConsecutive(
                           [PhpSelector::C_INI_DIRECTORY],
                           [PhpSelector::C_PATH]
                       )->willReturnOnConsecutiveCalls(
                            'Something',
                            'Else'
                       );

            $testPlugin->expects($this->never())
                       ->method('backupIni');

            $testPlugin->expects($this->never())
                       ->method('backupPath');

            $this->assertSame($testPlugin, $testPlugin->backupOriginals());
        }

        public function testBackupOriginalNotSet() {
            /** @var \PHPCI\Plugin\PhpSelector|\PHPUnit_Framework_MockObject_MockObject $testPlugin */
            $testPlugin = $this->getMockBuilder('\PHPCI\Plugin\PhpSelector')
                ->disableOriginalConstructor()
                ->setMethods(['getEnv', 'backupIni', 'backupPath'])
                ->getMock();


            $testPlugin->expects($this->exactly(2))
                ->method('getEnv')
                ->withConsecutive(
                    [PhpSelector::C_INI_DIRECTORY],
                    [PhpSelector::C_PATH]
                )->willReturnOnConsecutiveCalls(
                    NULL,
                    NULL
                );

            $testPlugin->expects($this->once())
                ->method('backupIni')
                ->willReturnSelf();

            $testPlugin->expects($this->once())
                ->method('backupPath')
                ->willReturnSelf();

            $this->assertSame($testPlugin, $testPlugin->backupOriginals());
        }

        public function testBackupIni() {
            /** @var \PHPCI\Plugin\PhpSelector|\PHPUnit_Framework_MockObject_MockObject $testPlugin */
            $testPlugin = $this->getMockBuilder('\PHPCI\Plugin\PhpSelector')
                               ->disableOriginalConstructor()
                               ->setMethods(['getEnv', 'addEnvironment'])
                               ->getMock();

            $testPlugin->expects($this->exactly(2))
                ->method('getEnv')
                ->with('PHPRC')
                ->willReturnOnConsecutiveCalls(
                    NULL,
                    '/Some/Path'
                );

            $testPlugin->expects($this->exactly(2))
                ->method('addEnvironment')
                ->withConsecutive(
                    [PhpSelector::C_INI_DIRECTORY, PhpSelector::C_NOT_SET],
                    [PhpSelector::C_INI_DIRECTORY, '/Some/Path']
                )->willReturnSelf();

            $reflectionPlugin = new \ReflectionMethod('\PHPCI\Plugin\PhpSelector', 'backupIni');
            $reflectionPlugin->setAccessible(TRUE);

            $this->assertSame($testPlugin, $reflectionPlugin->invoke($testPlugin));
            $this->assertSame($testPlugin, $reflectionPlugin->invoke($testPlugin));
        }

        public function testBackupPath() {
            $currentPath = getenv('PATH');

            /** @var \PHPCI\Plugin\PhpSelector|\PHPUnit_Framework_MockObject_MockObject $testPlugin */
            $testPlugin = $this->getMockBuilder('\PHPCI\Plugin\PhpSelector')
                               ->disableOriginalConstructor()
                               ->setMethods(['getEnv', 'addEnvironment'])
                               ->getMock();

            $testPlugin->expects($this->once())
                       ->method('getEnv')
                       ->with('PATH')
                       ->willReturn($currentPath);

            $testPlugin->expects($this->once())
                       ->method('addEnvironment')
                       ->with(PhpSelector::C_PATH, $currentPath)
                       ->willReturnSelf();

            $reflectionPlugin = new \ReflectionMethod('\PHPCI\Plugin\PhpSelector', 'backupPath');
            $reflectionPlugin->setAccessible(TRUE);

            $this->assertSame($testPlugin, $reflectionPlugin->invoke($testPlugin));
        }

        public function testLoadOriginals() {
            /** @var \PHPCI\Plugin\PhpSelector|\PHPUnit_Framework_MockObject_MockObject $testPlugin */
            $testPlugin = $this->getMockBuilder('\PHPCI\Plugin\PhpSelector')
                               ->disableOriginalConstructor()
                               ->setMethods(['getEnv', 'addEnvironment'])
                               ->getMock();

            $testPlugin->expects($this->exactly(2))
                ->method('getEnv')
                ->withConsecutive(
                    [PhpSelector::C_INI_DIRECTORY],
                    [PhpSelector::C_PATH]
                )->willReturnOnConsecutiveCalls(
                    PhpSelector::C_NOT_SET,
                    '/Some/Path'
                );

            $testPlugin->expects($this->exactly(2))
                ->method('addEnvironment')
                ->withConsecutive(
                    ['PHPRC', ''],
                    ['PATH' , '/Some/Path']
                )->willReturnSelf();

            $this->assertSame($testPlugin, $testPlugin->loadOriginals());
        }



        public function testPreparePath() {
            $currentPath = getenv('PATH');

            /** @var \PHPCI\Plugin\PhpSelector|\PHPUnit_Framework_MockObject_MockObject $testPlugin */
            $testPlugin = $this->getMockBuilder('\PHPCI\Plugin\PhpSelector')
                ->disableOriginalConstructor()
                ->setMethods(['getOption', 'addEnvironment', 'getEnv'])
                ->getMock();

            $testPlugin->expects($this->once())
                ->method('getOption')
                ->with('php_directory')
                ->willReturn('/Some/Directory');

            $testPlugin->expects($this->once())
                ->method('getEnv')
                ->with(PhpSelector::C_PATH)
                ->willReturn($currentPath);

            $testPlugin->expects($this->once())
                ->method('addEnvironment')
                ->with(
                    'PATH',
                    '/Some/Directory:' . $currentPath
                )->willReturnSelf();

            $this->assertSame($testPlugin, $testPlugin->preparePath());
        }
    }
