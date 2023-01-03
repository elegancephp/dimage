<?php

namespace Elegance\Trait;

use GdImage;

trait DImageGet
{
    /** Retorna o nome da imagem */
    function getName(bool $ex = false): string
    {
        $name = $this->name;

        if ($ex)
            $name .= '.' . $this->getExtension();

        return $name;
    }

    /** Retorna o caminho da imagem no disco */
    function getPath(): ?string
    {
        return $this->path;
    }

    /** Retorna a imagem GD gerada pela classe */
    function getGd(): GdImage
    {
        return $this->gd;
    }

    /** Retorna o array de dimensão da imagem */
    function getSize(): array
    {
        return $this->size;
    }

    /** Retorna a largura da imagem */
    function getWidth(): int
    {
        return $this->size[0];
    }

    /** Retorna a altura da imagem */
    function getHeight(): int
    {
        return $this->size[1];
    }

    /** Retorna a extensao da imagem */
    function getExtension(): string
    {
        return match ($this->imageType) {
            IMAGETYPE_BMP => 'bmp',
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_GIF => 'gif',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
        };
    }

    /** Retorna o tamanho do arquivo da imagem */
    function getFileSize(): float
    {
        return num_format(strlen($this->getBin()) / (1024 * 1024), 3, 1);
    }

    /** Captura o Hash Md5 gerado pelo binario da imagem */
    function getHash(): string
    {
        return md5($this->getBin());
    }

    /** Retorna o binario da imagem */
    function getBin(): string
    {
        ob_start();

        match ($this->imageType) {
            IMAGETYPE_BMP => imagebmp($this->gd, null, true),
            IMAGETYPE_JPEG => imagejpeg($this->gd, null, $this->quality),
            IMAGETYPE_GIF => imagegif($this->gd, null),
            IMAGETYPE_PNG => imagepng($this->gd, null),
            IMAGETYPE_WEBP => imagewebp($this->gd, null, $this->quality),
        };

        $bin = ob_get_contents();

        ob_end_clean();

        return $bin;
    }
}
