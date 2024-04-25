<?php

use Illuminate\Auth\GenericUser;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\Shared\PdoInterceptor\Laravel\MysqlConnection;
use MobileStock\service\EntregaService\EntregaServices;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RecalcularEtiquetasTest extends test\TestCase
{
    public function listaSituacoes(): array
    {
        return [
            'Recalcula entrega AB' => ['AB', true, 1, 1, null],
            'Recalcula entrega EX' => ['EX', true, 2, 3, 2],
            'Recalcula entrega PT' => ['PT', false, null, 0, null],
            'Recalcula entrega EN' => ['EN', false, null, 0, null],
        ];
    }

    /**
     * @dataProvider listaSituacoes
     */
    public function testRecalcularEtiquetas(
        string $situacao,
        bool $ehValido,
        ?int $volumeExistente,
        int $volumeAdicionar,
        ?int $etiquetasApagadas
    ): void {
        if (!$ehValido) {
            $this->expectException(BadRequestHttpException::class);
        }

        $connectionMock = $this->createPartialMock(MysqlConnection::class, [
            'selectOne',
            'select',
            'update',
            'delete',
            'insert',
        ]);
        $connectionMock->__construct($this->createMock(PDO::class));
        $connectionMock->method('selectOne')->willReturn(['situacao' => $situacao, 'volumes' => $volumeExistente]);
        $connectionMock->method('select')->willReturn([['id' => 47168, 'volume' => $volumeExistente]]);
        $connectionMock->method('delete')->willReturn($etiquetasApagadas);
        $connectionMock->method('insert')->willReturn($volumeAdicionar);
        $connectionMock->method('update')->willReturn(1);

        $databaseManangerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManangerMock->method('connection')->willReturn($connectionMock);

        DB::swap($databaseManangerMock);
        Model::setConnectionResolver(app('db'));

        Auth::setUser(
            new GenericUser([
                'id' => 1,
            ])
        );

        $entregasEtiquetas = new EntregaServices();
        $entregasEtiquetas->recalculaEtiquetas(1, $volumeAdicionar);

        $this->assertTrue(true);
    }

    public function testSituacaoNaoPermiteAlterar(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $connectionMock = $this->createPartialMock(MysqlConnection::class, ['selectOne']);
        $connectionMock->__construct($this->createMock(PDO::class));
        $connectionMock->method('selectOne')->willReturn(['situacao' => 'EN']);

        $databaseManangerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManangerMock->method('connection')->willReturn($connectionMock);
        DB::swap($databaseManangerMock);
        Auth::setUser(
            new GenericUser([
                'id' => 1,
            ])
        );

        $entregasEtiquetas = new EntregaServices();
        $entregasEtiquetas->recalculaEtiquetas(1, 1);
    }
}
