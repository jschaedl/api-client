<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__.'/src',
    ])
    ->append([__FILE__, '.php-cs-fixer.dist.php'])
    ->append([__FILE__, '.rector.php'])
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    // the tools run on the newest PHP; the migration rule sets below keep the code at the PHP 8.2 floor
    ->setUnsupportedPhpVersionAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@PHP8x2Migration:risky' => true,
        '@PHP8x2Migration' => true,
        'php_unit_method_casing' => false,
        'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],
    ])
    ->setFinder($finder)
;
