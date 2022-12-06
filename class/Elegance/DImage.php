<?php

namespace Elegance;

use Elegance\DImage\DImageException;
use Elegance\DImage\TraitDImageCalc;
use Elegance\DImage\TraitDImageEdit;
use Elegance\DImage\TraitDImageGet;
use Elegance\DImage\TraitDImageNormalize;
use Elegance\DImage\TraitDImageSet;
use Elegance\DImage\TraitDImageUse;
use GdImage;

class DImage
{
    use TraitDImageCalc;
    use TraitDImageEdit;
    use TraitDImageGet;
    use TraitDImageNormalize;
    use TraitDImageSet;
    use TraitDImageUse;

    protected GdImage $gd;
    protected array $color = ['255', '255', '255'];
    protected array $size = [1, 1];

    protected int $imageType = IMAGETYPE_JPEG;
    protected int $quality = 75;

    protected string $name;
    protected string $path = '.';

    /** Cria um objeto DImage monocromatica */
    static function _color(string|array $color = 'fff', int|array $size = 1): DImage
    {
        $object = new DImage;

        $object->size = self::normalizeSize($size);

        $object->color = self::normalizeColor($color);

        $object->name = implode('-', [
            'color',
            colorHex(implode(',', $object->color)),
            ...$object->size
        ]);

        $object->gd = imagecreatetruecolor(...$object->size);

        imagefill($object->gd, 0, 0, imagecolorallocate($object->gd, ...$object->color));

        return $object;
    }

    /** Cria um objeto DImage com base em uma URL de arquivo */
    static function _url(string $url): DImage
    {
        $object = new DImage();

        $object->name = basename($url);

        $object->imageType = exif_imagetype($url);

        $object->gd = match ($object->imageType) {
            IMAGETYPE_BMP => imagecreatefrombmp($url),
            IMAGETYPE_JPEG => imagecreatefromjpeg($url),
            IMAGETYPE_GIF => imagecreatefromgif($url),
            IMAGETYPE_PNG => imagecreatefrompng($url),
            IMAGETYPE_WEBP => imagecreatefromwebp($url),
            default => throw new DImageException('image type not supported')
        };

        $object->size = [imagesx($object->gd), imagesy($object->gd)];

        return $object;
    }

    /** Cria um objeto DImage com base em um arquivo */
    static function _file(string $path): DImage
    {
        if (!File::check($path))
            throw new DImageException('file not found');

        $object = new DImage();

        $object->name(File::getOnly($path));

        $object->imageType = exif_imagetype($path);

        $object->path = Dir::getOnly($path);

        $object->gd = match ($object->imageType) {
            IMAGETYPE_BMP => imagecreatefrombmp($path),
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_GIF => imagecreatefromgif($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default => throw new DImageException('image type not supported')
        };

        $object->size = [imagesx($object->gd), imagesy($object->gd)];

        if ($object->imageType == IMAGETYPE_JPEG) {
            match (exif_read_data($path)['Orientation'] ?? 1) {
                2 => $object->flipH(),
                3 => $object->rotate(180, true),
                4 => $object->rotate(180, true)->flipH(),
                5 => $object->rotate(-90, true)->flipH(),
                6 => $object->rotate(-90, true),
                7 => $object->rotate(90, true)->flipH(),
                8 => $object->rotate(90, true),
                default => null
            };
        }

        return $object;
    }

    /** Cria um objeto DImage com base em um arquivo enviado */
    static function _upload(array $uploadFile): DImage
    {
        return DImage::_file($uploadFile['tmp_name'])
            ->name($uploadFile['name'])
            ->path();
    }

    protected function __construct()
    {
    }

    function __destruct()
    {
        imagedestroy($this->gd);
    }

    function __toString()
    {
        return $this->getBin();
    }
}