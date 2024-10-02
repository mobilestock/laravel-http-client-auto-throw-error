<?php

namespace MobileStock\helper;

use Illuminate\Foundation\Bootstrap\SetRequestForConsole;
use Illuminate\Foundation\Console\Kernel;
use MobileStock\Commands\MonitorAlteracoesColaboradorLojas;

class ConsoleKernel extends Kernel
{
    protected $bootstrappers = [...Globals::BOOTSTRAPPERS, SetRequestForConsole::class];
    protected $commands = [MonitorAlteracoesColaboradorLojas::class];
}
