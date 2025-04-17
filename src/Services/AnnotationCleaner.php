<?php

namespace Howdy\Annotate\Services;

class AnnotationCleaner
{
    public function remove(string $contents): string
    {
        return preg_replace(
            '#/\*\* Schema Information.*?\*/\n\n#s',
            '',
            $contents
        );
    }

    public function hasAnnotation(string $contents): bool
    {
        return preg_match('#/\*\* Schema Information.*?\*/\n\n#s', $contents) === 1;
    }
}
