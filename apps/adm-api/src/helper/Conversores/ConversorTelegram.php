<?php

namespace MobileStock\helper\Conversores;

use Illuminate\Support\Str;
use Monolog\Formatter\NormalizerFormatter;

class ConversorTelegram extends NormalizerFormatter
{
    public function format(array $record): string
    {
        unset($record['context']['request']);

        $converteArrayPalavras = fn(array $palavras) => mb_strtoupper(implode('\_', $palavras));
        $rawTitle = $record['context']['title'] ?? ($record['extra']['title'] ?? $record['channel']);
        $title = $converteArrayPalavras(
            array_map(
                fn($palavra) => mb_strtoupper($palavra) === $palavra
                    ? $palavra
                    : $converteArrayPalavras(Str::ucsplit($palavra)),
                str_word_count($rawTitle, 1)
            )
        );
        unset($record['context']['title'], $record['extra']['title']);
        $context = parent::format($record['extra']);
        $context = array_merge($context, parent::format(['origem' => "adm-api.$rawTitle"] + $record['context']));

        $mensagemDestaque = str_replace(
            ['.', '-', '(', ')', '!', '_', '>', '<'],
            ['\.', '\-', '\(', '\)', '\!', '\_', '\>', '\<'],
            $record['message']
        );
        $context = $context
            ? '```' .
                PHP_EOL .
                Str::limit(
                    json_encode($context, JSON_PRETTY_PRINT),
                    4096 - mb_strlen($title) - 22 - mb_strlen($mensagemDestaque) - 3
                ) .
                PHP_EOL .
                '```'
            : '';

        $mensagem = "\#$title" . PHP_EOL;
        $mensagem .= $mensagemDestaque . PHP_EOL;
        $mensagem .= $context;
        $mensagem = mb_substr($mensagem, 0, 4096);

        return $mensagem;
    }
}
