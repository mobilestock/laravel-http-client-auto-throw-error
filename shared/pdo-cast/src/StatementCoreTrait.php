<?php

namespace MobileStock\PdoCast;

use Illuminate\Contracts\Pipeline\Pipeline;

trait StatementCoreTrait
{
    protected Pipeline $pipeline;

    private function __construct(Pipeline $pipeline)
    {
        $this->pipeline = $pipeline;
    }

    public function call(string $methodName, array $args)
    {
        $statementCall = function (string $methodName, ...$args) {
            return call_user_func_array([$this, 'parent::' . $methodName], $args);
        };

        $tratado = $this->pipeline
            ->send([
                'stmt_method' => $methodName,
                'stmt_call' => $statementCall,
            ])
            ->then(fn() => $statementCall($methodName, ...$args));

        return $tratado;
    }
}
