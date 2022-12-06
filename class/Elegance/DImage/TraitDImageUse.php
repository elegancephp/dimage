<?php

namespace Elegance\DImage;

use Elegance\Dir;
use Elegance\File;
use Elegance\DImage;
use Elegance\Response;
use Error;

trait TraitDImageUse
{
    /** Salva a imagem em um arquivo */
    function save(?string $path = null): static
    {
        $path = $path ?? $this->path;

        if (is_null($path))
            throw new Error('É preciso definir um caminho para salvar o arquivo');

        $this->path = $path;


        Dir::create($path);

        $file = path($path, $this->getName());

        File::ensure_extension($file, $this->getExtension());

        match ($this->imageType) {
            IMAGETYPE_BMP => imagebmp($this->gd, $file, true),
            IMAGETYPE_JPEG => imagejpeg($this->gd, $file, $this->quality),
            IMAGETYPE_GIF => imagegif($this->gd, $file),
            IMAGETYPE_PNG => imagepng($this->gd, $file),
            IMAGETYPE_WEBP => imagewebp($this->gd, $file, $this->quality),
        };

        return $this;
    }

    /** Retorna uma copia do objeto de imagem atual*/
    function copy(): DImage
    {
        return clone ($this);
    }
}