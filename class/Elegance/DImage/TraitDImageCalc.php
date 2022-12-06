<?php

namespace Elegance\DImage;

trait TraitDImageCalc
{
    /** Garante que uma variavel tenha um array de redimencionamento */
    protected function ensureResizeArray(int|array &$size): void
    {
        list($width, $height) = $this->size;
        if (!is_array($size)) {
            switch (($width <=> $height) * -1) {
                case -1:
                    $size = [$size, 0];
                    break;
                case 0:
                    $size = [$size, $size];
                    break;
                case 1:
                    $size = [0, $size];
                    break;
            }
        }
    }

    /** Calcula valores para uma dimensão estipulando um valor maximo */
    protected function calcSizeMax(int|array $size): array
    {
        $this->ensureResizeArray($size);
        list($width, $height) = $this->size;
        list($modWidth, $modHeight) = $size;

        if ($modWidth && $width > $modWidth) {
            $height = $height / ($width / $modWidth);
            $width = $modWidth;
        }

        if ($modHeight && $height > $modHeight) {
            $width = $width / ($height / $modHeight);
            $height = $modHeight;
        }

        return [$width, $height];
    }

    /**
     * Calcula valores para uma dimensão estipulando um valor minio
     * @param int|int[] $size Tamanho desejado
     */
    protected function calcSizeMin(int|array $size): array
    {
        list($width, $height) = $this->size;
        if ($width <= $height) {
            if ($width > $size) {
                $height = $height / ($width / $size);
                $width = $size;
            }
        } else {
            if ($height > $size) {
                $width = $width / ($height / $size);
                $height = $size;
            }
        }
        return [$width, $height];
    }

    /**
     * Calcula coordenadas de uma posição na imagem
     * @param int $position <b>(0~8)</b>
     * @param int $dx Dimensão do enquadramento horizontal
     * @param int $dy Dimensão do enquadramento vertical
     */
    protected function calcPosition(int|array $position, int $dx = 0, int $dy = 0): array
    {
        if (is_array($position)) {
            $x = intval(array_shift($position) ?? 0);
            $y = intval(array_shift($position) ?? 0);
        } else {
            list($width, $height) = $this->size;

            $position = num_interval($position, 0, 8);

            $x =  match ($position) {
                0, 3, 7 => ($width / 2) - ($dx / 2),
                1, 2, 8 => 0,
                4, 5, 6 => $width - $dx,
            };

            $y =  match ($position) {
                0, 1, 5 => ($height / 2) - ($dy / 2),
                2, 3, 4 => 0,
                6, 7, 8 => $height - $dy,
            };
        }
        return [$x, $y];
    }
}
