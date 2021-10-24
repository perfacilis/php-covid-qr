# php-covid-qr

## What's this repository for?

The main goal is to better understand the inner workings of the Corona QR codes.
Currently supports decoding Domestic Dutch (NL) QR codes and European Greenpass codes.

No support for supplying QR images yet.

## Setup

First make sure you have php7.3 or later and the bcmath extension is availeble.
Then just clone the repo and install requirements trough composer:

```bash
composer install
```

## Usage

```bash
php -f cli.php data.txt
php -f cli.php 'NL2:F00D8A8E...'
php -f cli.php 'HC1:0D15EA5E...'
```

## Contribution

Have any ideas how to improve code? Just shoot in your pull request.
Feel free to add extra samples, one QR per line please.

Domestic QR's from other countries are also very welcome, same goes for any logic for decoding them.

## Contact

Feel free to contact me trough mail or SM for questions or ideas.