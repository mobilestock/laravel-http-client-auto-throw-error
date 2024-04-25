<?php

namespace test\unit\queue;

use Illuminate\Support\Facades\DB;
use PDO;

class JobFake
{
    protected int $opcao;
    protected PDO $conexao;

    public function __construct(PDO $conexao, int $opcao)
    {
        $this->opcao = $opcao;
        $this->conexao = $conexao;
    }

    public function handle(): void
    {
        DB::setPdo($this->conexao);
    }
}
