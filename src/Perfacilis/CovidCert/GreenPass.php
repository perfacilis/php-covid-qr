<?php

namespace Perfacilis\CovidCert;

use CBOR\Decoder;
use CBOR\OtherObject\OtherObjectManager;
use CBOR\StringStream;
use CBOR\Tag\TagObjectManager;
use Mhauri\Base45;
use Perfacilis\CovidCert\AbstractCovidCert;

/**
 * @author Roy Arisse <support@perfacilis.com>
 * @copyright (c) 2021, Perfacilis
 */
class GreenPass extends AbstractCovidCert
{
    public function __construct(string $stripped_data)
    {
        // Decode to zip
        $base45 = new Base45();
        $zlibencoded = $base45->decode($stripped_data);

        $cbor = zlib_decode($zlibencoded);

        $otherObjectManager = new OtherObjectManager();
        $tagManager = new TagObjectManager();
        $decoder = new Decoder($tagManager, $otherObjectManager);

        $stream = new StringStream($cbor);
        $decoded = $decoder->decode($stream)->getNormalizedData();

        // $headers1 = $decoded->get(0);
        // $headers2 = $decoded->get(1);
        $cbor_data = $decoded->get(2);
        // $signature = $decoded->get(3);

        $stream = new StringStream($cbor_data->getValue());
        $this->cbor_data = $decoder->decode($stream)->getNormalizedData();
    }
    
    public function jsonSerialize(): array
    {
        return $this->cbor_data;
    }

    public function __toString(): string
    {
        $json = $this->jsonSerialize();

        $s = ''
            . 'QR Code Issuer: ' . $json[1] . PHP_EOL
            . 'QR Code Expiry: ' . date(DATE_COOKIE, $json[4]) . PHP_EOL
            . 'QR Code Generated: ' . date(DATE_COOKIE, $json[6]) . PHP_EOL
            . PHP_EOL;

        // Load schema for the rest
        $schema = file_get_contents('https://raw.githubusercontent.com/ehn-dcc-development/ehn-dcc-schema/release/1.3.0/DCC.combined-schema.json');
        $schema = json_decode($schema, true);

        $s .= $this->annotate($json[-260][1], $schema, $schema['properties'])
            . PHP_EOL;

        return $s;
    }

    /**
     * @var array
     */
    private $cbor_data = [];

    private function annotate(array $data, array $schema, array $properties, int $depth = 0): string
    {
        $s = '';

        foreach ($data as $k => $v) {
            $label = isset($properties[$k]) ? ($properties[$k]['title'] ?? $properties[$k]['description']) : $k;
            $label = trim(strtok($label, '-'));

            // Tell us: where are we
            $s .= str_repeat('  ', $depth) . $label . ': ';

            // List: non associative array
            if (is_array($v) && array_key_exists(0, $v)) {
                $s .= PHP_EOL;

                $ref = substr($schema['properties'][$k]['items']['$ref'], strrpos($schema['properties'][$k]['items']['$ref'], '/') + 1);
                foreach ($v as $vv) {
                    $s .= $this->annotate($vv, $schema, $schema['$defs'][$ref]['properties'], $depth + 1);
                }
            }
            // Dict: associative array
            elseif (is_array($v)) {
                $ref = substr($properties[$k]['$ref'], strrpos($properties[$k]['$ref'], '/') + 1);
                $s .= PHP_EOL
                    . $this->annotate($v, $schema, $schema['$defs'][$ref]['properties'], $depth + 1);
            }
            // Scalar value
            else {
                $s .= $v . PHP_EOL;
            }
        }

        return $s;
    }
}
