<?php

namespace MobileStock\helper;

interface RetryableException extends \Throwable
{
    public function retries(int $routeRetries);
}