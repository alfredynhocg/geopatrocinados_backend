<?php

namespace App\Infrastructure\Impresion\Services;

use Exception;
use GdImage;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\GdEscposImage;

/**
 * mike42/escpos-php v2.2 (última versión publicada) valida las imágenes GD con
 * is_resource(), pero desde PHP 8 las funciones de GD devuelven instancias de
 * GdImage en vez de resources, así que esa validación siempre falla ("Failed to
 * load image.") aunque la imagen se haya cargado correctamente. Se sobreescribe
 * el método con el mismo cuerpo, aceptando también instancias de GdImage.
 */
class Php8GdEscposImage extends GdEscposImage
{
    public function readImageFromGdResource($im)
    {
        if (! is_resource($im) && ! $im instanceof GdImage) {
            throw new Exception('Failed to load image.');
        } elseif (! EscposImage::isGdLoaded()) {
            throw new Exception(__FUNCTION__.' requires \'gd\' extension.');
        }

        $imgHeight = imagesy($im);
        $imgWidth = imagesx($im);
        $imgData = str_repeat("\0", $imgHeight * $imgWidth);
        for ($y = 0; $y < $imgHeight; $y++) {
            for ($x = 0; $x < $imgWidth; $x++) {
                $cols = imagecolorsforindex($im, imagecolorat($im, $x, $y));
                $greyness = (int) (($cols['red'] + $cols['green'] + $cols['blue']) / 3) >> 7;
                $black = (1 - $greyness) >> ($cols['alpha'] >> 6);
                $imgData[$y * $imgWidth + $x] = $black;
            }
        }
        $this->setImgWidth($imgWidth);
        $this->setImgHeight($imgHeight);
        $this->setImgData($imgData);
    }
}
