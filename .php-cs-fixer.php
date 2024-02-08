<?php

$config = new PhpCsFixer\Config();
$config->setUsingCache(false);
return $config->setRules([
    'array_syntax' => ['syntax' => 'short'],
    'elseif' => true,
    'combine_consecutive_issets' => true,
    'combine_consecutive_unsets' => true,
    'mb_str_functions' => true,
    'array_push' => true,
    'fully_qualified_strict_types' => true,
    'no_unused_imports' => true,
    'single_line_after_imports' => true,
    'logical_operators' => true,
    'no_alternative_syntax' => true,
    'modernize_types_casting' => true,
    'lambda_not_used_import' => true,

    'lowercase_cast' => true,
    'short_scalar_cast' => true,
    'no_unset_cast' => true,
    'lowercase_keywords' => true,
    'lowercase_static_reference' => true,
    'native_function_type_declaration_casing' => true,
    'ternary_to_null_coalescing' => true,
]);
