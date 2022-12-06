<?php

namespace Elegance\DImage;

trait TraitDImageNormalize
{
    /** Normaliza uma variavel de cores */
    protected static function normalizeColor(string|array $color): array
    {
        if (!is_array($color)) {
            $color = colorRGB($color);
            $color = explode(',', $color);
            $color = [
                $color[0], $color[1], $color[2],
            ];
        }
        return $color;
    }

    /** Normaliza entradas de tamanho */
    protected static function normalizeSize(int|array $size): array
    {
        $size = is_array($size) ? $size : [$size];

        $size[] = $size[0];

        if ($size[1] == 0)
            $size[1] = $size[0];

        if ($size[0] == 0)
            $size[0] = $size[1];

        $size = [array_shift($size), array_shift($size)];

        $size = array_map(fn ($v) => max($v, 0), $size);

        return $size;
    }
}