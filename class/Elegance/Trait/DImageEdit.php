<?php

namespace Elegance\Trait;

use Elegance\DImage;
use Elegance\Exception\DImageException;

trait DImageEdit
{
    /** Converte o arquivo de saida para outro formato */
    function convert(string $ex): static
    {
        $ex = strtolower($ex);
        $ex = trim($ex, ' .');
        $newImageType = match ($ex) {
            'bmp', => IMAGETYPE_BMP,
            'png', => IMAGETYPE_PNG,
            'gif', => IMAGETYPE_GIF,
            'webp', => IMAGETYPE_WEBP,
            'jpg', 'jpe', 'jpeg' => IMAGETYPE_JPEG,
            default => throw new DImageException('image type not supported')
        };

        if ($newImageType != $this->imageType) {
            switch ($newImageType) {
                case IMAGETYPE_PNG:
                case IMAGETYPE_GIF:
                case IMAGETYPE_WEBP:
                    $c = $this->color;
                    $tmp = imagecreatetruecolor(...$this->size);
                    imagefill($tmp, 0, 0, imagecolorallocatealpha($tmp, $c[0], $c[1], $c[2], 127));
                    imagecopyresampled($tmp, $this->gd, 0, 0, 0, 0, $this->size[0], $this->size[1], ...$this->size);
                    imagealphablending($tmp, true);
                    imagesavealpha($tmp, true);
                    $this->gd = $tmp;
                    break;
            }
            $this->imageType = $newImageType;
        }
        return $this;
    }

    /** Redimenciona a imagem respeitando a proporção */
    function resize(int|array $size): static
    {
        if (is_int($size) && $size < 0) {
            $size = $this->calcSizeMin($size * -1);
        } else {
            $size = $this->calcSizeMax($size);
        }
        $this->ensureResizeArray($size);
        $this->resizeFree($size);
        return $this;
    }

    /** Redimenciona a imagem não respeitando a proporção */
    function resizeFree(int|array $size): static
    {
        $this->ensureResizeArray($size);

        list($nw, $nh) = $size;
        list($w, $h) = $this->size;

        $nw = intval($nw ? $nw : $w);
        $nh = intval($nh ? $nh : $h);

        switch ($this->imageType) {
            case IMAGETYPE_JPEG:
            case IMAGETYPE_BMP:
                $tmp = imagecreatetruecolor($nw, $nh);
                imagecopyresampled($tmp, $this->gd, 0, 0, 0, 0, $nw, $nh, $w, $h);
                break;
            case IMAGETYPE_GIF:
            case IMAGETYPE_PNG:
            case IMAGETYPE_WEBP:
                $tmp = imagecreatetruecolor($nw, $nh);
                imagealphablending($tmp, false);
                imagesavealpha($tmp, true);
                imagecopyresampled($tmp, $this->gd, 0, 0, 0, 0, $nw, $nh, $w, $h);
                break;
        }

        $this->gd = $tmp;
        $this->size = [$nw, $nh];

        return $this;
    }

    /** Rotaciona uma imagem. */
    function rotate(int $graus, bool $transparent = true): static
    {
        $graus = $graus < 0 ? 360 + $graus : $graus;

        $c = $this->color;

        if ($transparent)
            $this->convert('webp');

        switch ($this->imageType) {
            case IMAGETYPE_JPEG:
            case IMAGETYPE_BMP:
                $this->gd = imagerotate($this->gd, $graus, imagecolorallocate($this->gd, $c[0], $c[1], $c[2]));
                break;
            case IMAGETYPE_GIF:
            case IMAGETYPE_PNG:
            case IMAGETYPE_WEBP:
                $this->gd = imagerotate($this->gd, $graus, imagecolorallocatealpha($this->gd, $c[0], $c[1], $c[2], 127));
                imagealphablending($this->gd, true);
                imagesavealpha($this->gd, true);
                break;
        }

        $this->size = [imagesx($this->gd), imagesy($this->gd)];

        return $this;
    }

    /** Inverte a imagem na horizontal */
    function flipH(): static
    {
        imageflip($this->gd, IMG_FLIP_HORIZONTAL);
        return $this;
    }

    /** Inverte a imagem na vertical */
    function flipV(): static
    {
        imageflip($this->gd, IMG_FLIP_VERTICAL);
        return $this;
    }

    /** Adiciona uma imagem dmx em uma posição da imagem atual */
    function stamp(DImage $imgSpamt, int $position = 0): static
    {
        #Capturando imagem
        $imgSpamt->resize(min(...$this->size));
        $stamp = $imgSpamt->getGd();
        imageAlphaBlending($stamp, true);
        imageSaveAlpha($stamp, true);

        $position = $this->calcPosition($position, imagesx($stamp), imagesy($stamp));

        imagecopy($this->gd, $stamp, $position[0], $position[1], 0, 0, imagesx($stamp), imagesy($stamp));
        return $this;
    }

    /** Corta uma parte da imagem */
    function crop(int|array $size, int $position = 0): static
    {
        $size = is_array($size) ? $size : [$size];
        $size[] = $size[0];
        if ($size[1] == 0) {
            $size[1] = $size[0];
        }
        if ($size[0] == 0) {
            $size[0] = $size[1];
        }
        $size = [array_shift($size), array_shift($size)];
        if ($size == [0, 0]) {
            $size[0] = [1, 1];
        }

        list($width, $height) = $this->size;

        if ($size[0] > $width) {
            $size[1] = ($size[1] * $width) / $size[0];
            $size[0] = $width;
        }
        if ($size[1] > $height) {
            $size[0] = ($size[0] * $height) / $size[1];
            $size[1] = $height;
        }

        list($nw, $nh) = $size;
        $color = $this->color;

        $quadro = imagecreatetruecolor($nw, $nh);
        imagefill($quadro, 0, 0, imagecolorallocatealpha($this->gd, $color[0], $color[1], $color[2], 127));

        $w = $width;
        $h = $height;

        $this->size = [$nw, $nh];

        list($px, $py) = $this->calcPosition($position, $w, $h);

        $px = round($px, 0);
        $py = round($py, 0);

        imagecopyresampled($quadro, $this->gd, $px, $py, 0, 0, $w, $h, $w, $h);
        $this->gd = $quadro;
        imagealphablending($this->gd, true);
        imagesavealpha($this->gd, true);
        return $this;
    }

    /** Enquadra imagem */
    function framing(int|array $size): static
    {
        $this->resize(is_array($size) ? $size : $size);

        $size = is_array($size) ? $size : [$size];
        $size[] = $size[0];
        if ($size[1] == 0) {
            $size[1] = $size[0];
        }
        if ($size[0] == 0) {
            $size[0] = $size[1];
        }
        $size = [array_shift($size), array_shift($size)];
        if ($size == [0, 0]) {
            $size[0] = [1, 1];
        }

        list($width, $height) = $this->size;

        $quadro = imagecreatetruecolor($size[0], $size[1]);
        imagefill($quadro, 0, 0, imagecolorallocate($quadro, ...$this->color));
        $px = ($size[0] / 2) - ($width / 2);
        $py = ($size[1] / 2) - ($height / 2);
        imagecopyresampled($quadro, $this->gd, $px, $py, 0, 0, $width, $height, $width, $height);
        $this->gd = $quadro;
        return $this;
    }

    /** Aplica um filtro GD a imagem */
    function filter(int $filter): static
    {
        imagefilter($this->gd, ...func_get_args());
        return $this;
    }
}
