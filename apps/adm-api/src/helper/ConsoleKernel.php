<?php

namespace MobileStock\helper;

use Illuminate\Foundation\Bootstrap\SetRequestForConsole;
use Illuminate\Foundation\Console\Kernel;

class ConsoleKernel extends Kernel
{
    protected $bootstrappers = [
        ...Globals::BOOTSTRAPPERS,
        SetRequestForConsole::class,
    ];
}