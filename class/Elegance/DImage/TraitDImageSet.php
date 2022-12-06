<?php

namespace Elegance\DImage;

trait TraitDImageSet
{
    /** Define a qualidade da imagem para arquivos exportados */
    function quality(int $quality): static
    {
        $this->quality = num_interval($quality, 0, 100);
        return $this;
    }

    /** Define um nome para o arquivo */
    function name(string $name): static
    {
        if (!str_starts_with($name, '.') && str_contains($name, '.')) {
            $name = explode('.', $name);
            array_pop($name);
            $name = implode('.', $name);
        }

        $this->name = $name;

        return $this;
    }

    /** Define um caminho onde o arquivo será armazenado */
    function path(): static
    {
        $this->path = path(...func_get_args());
        return $this;
    }

    /** Define a cor base da imagem */
    function color(string|array $color): static
    {
        $this->color = $this->normalizeColor($color);
        return $this;
    }
}