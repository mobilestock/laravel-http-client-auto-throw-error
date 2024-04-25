<?php

class LogLevelTest extends \test\TestCase
{
    public function testEnvironmentSet(): void
    {
        putenv('APP_LOG_LEVEL=emergency');

        $this->assertEquals('emergency', app('log_level'));
    }

    public function testEnvironmentPriority(): void
    {
        putenv('APP_LOG_LEVEL=critical');
        app()['config']['logging.level'] = 'debug';

        $this->assertEquals('critical', app('log_level'));
    }

    public function testProgrammaticallySet(): void
    {
        putenv('APP_LOG_LEVEL');
        app()['config']['logging.level'] = 'debug';

        $this->assertEquals('debug', app('log_level'));
    }
}
