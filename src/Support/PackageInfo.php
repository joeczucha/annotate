<?php

namespace Howdy\Annotate\Support;

class PackageInfo
{
    protected static ?array $data = null;

    protected static function load(): void
    {
        if (self::$data !== null) {
            return;
        }

        $path = __DIR__ . '/../../composer.json';

        if (!file_exists($path)) {
            self::$data = [];
            return;
        }

        self::$data = json_decode(file_get_contents($path), true) ?? [];
    }

    public static function name(): string
    {
        return 'Laravel Annotator';
    }

    public static function version(): string
    {
        self::load();

        return self::$data['version'] ?? 'dev';
    }

    public static function author(): string
    {
        self::load();

        if (!empty(self::$data['authors'][0]['name'])) {
            $author = self::$data['authors'][0]['name'];
            $email = self::$data['authors'][0]['email'] ?? null;

            return $email ? "{$author} <{$email}>" : $author;
        }

        return 'Unknown Author';
    }

    public static function lines(): array
    {
        return [
            ucwords(str_replace('/', ' ', self::name())),
            self::author(),
            'Version: v' . self::version(),
        ];
    }
}
