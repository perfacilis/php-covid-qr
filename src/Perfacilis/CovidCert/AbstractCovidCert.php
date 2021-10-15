<?php

namespace Perfacilis\CovidCert;

use Exception;
use JsonSerializable;
use RuntimeException;

/**
 * @author Roy Arisse <support@perfacilis.com>
 * @copyright (c) 2021, Perfacilis
 */
abstract class AbstractCovidCert implements JsonSerializable
{
    abstract public function __toString(): string;

    /**
     * @param string $stripped_data Raw data without header
     */
    abstract public function __construct(string $stripped_data);

    // Known headers
    public const GREENPASS = 'HC1';
    public const DOMESTIC_NL = 'NL2';

    public static function fromFile(string $filename): self
    {
        if (!is_readable($filename)) {
            throw new RuntimeException(sprintf(
                'Unable to read file %s.',
                $filename
            ));
        }

        $handle = fopen($filename, 'r');
        $first_line = fgets($handle);
        fclose($handle);

        return self::fromString($first_line);
    }

    public static function fromString(string $qr_data): self
    {
        [$header, $stripped_data] = preg_split('/[:]/', $qr_data, 2);
        $stripped_data = trim($stripped_data);

        switch ($header) {
            case self::GREENPASS:
                return new GreenPass($stripped_data);
            // nobreak

            case self::DOMESTIC_NL:
                return new DomesticNl($stripped_data);
            // nobreak
        }

        throw new RuntimeException(sprintf(
            'Unknown headers \'%s\' found in QR data \'%s\'.',
            $header,
            $qr_data
        ));
    }

    public static function fromQrImage(string $path_to_image): self
    {
        throw new Exception(sprintf(
            'Method %s not yet implemented.',
            __METHOD__
        ));
    }
}
