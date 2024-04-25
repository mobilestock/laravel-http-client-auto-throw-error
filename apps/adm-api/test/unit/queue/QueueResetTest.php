<?php

use Illuminate\Queue\Worker;
use Illuminate\Support\Facades\DB;
use test\unit\queue\JobFake;

class QueueResetTest extends test\TestCase
{
    public function testDeveResetarConexaoFila(): void
    {
        $this->runJob(1);
        $worker = app('queue.worker');
        $primeiraConexao = spl_object_id(DB::getPdo());
        Closure::bind(
            function () {
                /** @var Worker $this */
                ($this->resetScope)();
            },
            $worker,
            $worker
        )();

        $this->assertNull(DB::getRawPdo());

        $this->runJob(2);
        $this->assertNotEquals($primeiraConexao, spl_object_id(DB::getPdo()));
    }

    public function runJob(int $opcao): void
    {
        if ($opcao > 2) {
            return;
        }
        $job = new JobFake($this->createMock(PDO::class), $opcao);
        $job->handle();
    }
}
