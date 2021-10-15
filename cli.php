<?php

use Perfacilis\CovidCert\AbstractCovidCert;

/**
 * @author Roy Arisse <support@perfacilis.com>
 * @copyright (c) 2021, Perfacilis
 * @see https://gir.st/blog/greenpass.html
 */
require_once __DIR__ . '/vendor/autoload.php';

$data = $argv[1] ?? '';
if (!$data) {
    echo 'Error: no input data received!', PHP_EOL;
    echo 'Usage: php -f ', __FILE__, ' data.txt', PHP_EOL;
    echo '  -    php -f ', __FILE__, ' HC1:...', PHP_EOL, PHP_EOL;
    exit(1);
}

echo 'Reading EHN DCC: European eHealth Network - Digital Covid Certificate', PHP_EOL;

if (is_file($data)) {
    $cert = AbstractCovidCert::fromFile($data);
} else {
    $cert = AbstractCovidCert::fromString($data);
}

echo (string) $cert;
exit;
