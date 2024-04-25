<?php

namespace MobileStock\service;

use MobileStock\helper\DB;
use PDO;

class TiposGradeService
{
    public static function buscaTiposGrade(PDO $conexao): array
    {
        $consulta = DB::select('SELECT produtos_tipos_grades.id, produtos_tipos_grades.nome, produtos_tipos_grades.grade_json, produtos_tipos_grades.criado_em FROM produtos_tipos_grades', [], $conexao);

        $consulta = array_map(function(array $tipoGrade) {
            $tipoGrade['grade_json'] = json_decode($tipoGrade['grade_json'], true);

            return $tipoGrade;
        }, $consulta);

        return $consulta;
    }

    public static function buscaTiposGradeSemFormatacaoJson(PDO $conexao): array
    {
        $consulta = DB::select('SELECT produtos_tipos_grades.id, produtos_tipos_grades.nome, produtos_tipos_grades.grade_json, produtos_tipos_grades.criado_em FROM produtos_tipos_grades', [], $conexao);
        return $consulta;
    }
}