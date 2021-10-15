<?php

namespace Perfacilis\CovidCert;

use FG\ASN1\ASNObject;
use Perfacilis\CovidCert\AbstractCovidCert;

/**
 * @author Roy Arisse <support@perfacilis.com>
 * @copyright (c) 2021, Perfacilis
 */
class DomesticNl extends AbstractCovidCert
{
    /**
     * @see https://github.com/minvws/nl-covid19-coronacheck-idemix/blob/main/common/common.go#L56
     * @param string $stripped_data
     */
    public function __construct(string $stripped_data)
    {
        // Returns an "Idemix" document...
        $binary = $this->decodeBase45Nl($stripped_data);

        // Get ASN1 object properties
        $object = ASNObject::fromBinary($binary);
        $decoded = $this->decodeAsn1($object)[0];

        // Annotate
        $this->properties = array_combine([
            'disclosureTimeSeconds',
            'C',
            'A',
            'EResponse',
            'VResponse',
            'AResponse',
            'ADisclosed'
            ], $decoded);
    }

    public function __toString(): string
    {
        $json = $this->jsonSerialize();
        $validUntil = $json['validFrom'] + $json['validForHours'] * 3600;

        $s = ''
            . 'QR Code Issuer: ' . $json['issuer'] . PHP_EOL
            . 'QR Code Version: ' . $json['version'] . PHP_EOL
            . 'QR Code Valid from: ' . date(DATE_COOKIE, $json['validFrom']) . PHP_EOL
            . 'QR Code Valid until: ' . date(DATE_COOKIE, $validUntil) . PHP_EOL
            . PHP_EOL;

        foreach ($json as $k => $v) {
            $s .= ' - ' . $k . ': ' . $v . PHP_EOL;
        }

        return $s;
    }

    public function jsonSerialize(): array
    {
        $meta = $this->getMetaData();
        $attr = $this->getAttributes();

        return array_merge($meta, $attr);
    }

    private $properties = [];

    /**
     * @return string[]
     */
    private function getMetaData(): array
    {
        // Meta data
        $meta = $this->properties['ADisclosed'][0];
        $meta_binary = $this->decodeInt($meta);

        $meta_object = ASNObject::fromBinary($meta_binary);
        $metadata = $this->decodeAsn1($meta_object);

        return array_combine(['version', 'issuer'], $metadata[0]);
    }

    /**
     * @see https://github.com/minvws/nl-covid19-coronacheck-idemix/blob/main/common/common.go#L25
     * @return string[]
     */
    private function getAttributes(): array
    {
        $titles = [
            'isSpecimen',
            'isPaperProof',
            'validFrom',
            'validForHours',
            'firstNameInitial',
            'lastNameInitial',
            'birthDay',
            'birthMonth'
        ];

        $attributes = [];
        foreach ($titles as $i => $title) {
            $value = $this->decodeInt($this->properties['ADisclosed'][$i + 1]);
            $attributes[$title] = $value;
        }

        return $attributes;
    }

    /**
     * Dutch QR uses non standardized base45 decode derived from base58 decode
     * using a custom alphabet, because they needed more space or whatever.
     *
     * Using bcmath, because numbers are way over PHP_INT_MAX.
     * This makes process slow though...
     *
     * @see https://www.bartwolff.com/Blog/2021/08/21/decoding-the-dutch-domestic-coronacheck-qr-code
     * @see https://gist.github.com/confiks/8fcb480d87a50cf1bb5e40e2f0930fad
     * @see https://github.com/minvws/base45-go/issues/1
     * @todo See if there is a method not using bcmath, this uses lots of cpu
     * @staticvar string $alphabet
     * @param string
     * @return string Binary result data as string
     */
    private function decodeBase45Nl(string $encoded): string
    {
        static $alphabet = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:";

        $len = strlen($encoded);

        $acc = 0;
        for ($i = 0; $i < $len; $i += 1) {
            $f = strpos($alphabet, $encoded[$i]);
            $w = bcpow(45, $len - $i - 1);
            $acc = bcadd($acc, bcmul($f, $w));
        }

        $result = '';
        while ($acc > 0) {
            $mod = bcmod($acc, 256, 0);
            $acc = bcdiv($acc, 256, 0);
            $result = chr($mod) . $result;
        }

        return $result;
    }

    /**
     * Resurse trough ASN object and resturn array with literal data.
     * Data is represented in integer strings, which can be decoded using
     * decodeInt.
     *
     * @see https://github.com/FGrosse/PHPASN1
     * @param ASNObject $object
     * @return array
     */
    private function decodeAsn1(ASNObject $object): array
    {
        $res = [];

        $content = $object->getContent();
        if (is_array($content)) {
            $content = [];
            foreach ($object as $child) {
                $content = array_merge($content, $this->decodeAsn1($child));
            }
        }

        $res[] = $content;

        return $res;
    }

    /**
     * Decode integer string into binary data
     * Data with trailing 0 always resolves in empty string.
     * Using bcmath, because numbers are way over PHP_INT_MAX.
     *
     * @param string $data
     * @return string
     */
    private function decodeInt(string $data): string
    {
        // Least significant bit 0 means NULL
        // if ($data & 0)
        if (substr($data, -1) == '0') {
            return '';
        }

        // $acc = $data >> 1
        $acc = bcdiv($data, 2);

        $result = '';
        while ($acc > 0) {
            $mod = bcmod($acc, 256, 0);
            $acc = bcdiv($acc, 256, 0);
            $result = chr($mod) . $result;
        }

        return $result;
    }
}
