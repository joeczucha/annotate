<?php

namespace Howdy\Annotate\Support;

use Illuminate\Console\Command;

class HeaderPrinter
{
    public static function print(Command $console): void
    {
        $lines = PackageInfo::lines();

        $maxLength = collect($lines)->map('strlen')->max();
        $padding = 4;
        $boxWidth = $maxLength + $padding;

        $border = '+' . str_repeat('-', $boxWidth) . '+';

        $console->line('');
        $console->line($border);

        $console->line('| <fg=#cc0000>' . str_pad($lines[0], $boxWidth - 2, ' ', STR_PAD_RIGHT) . '</> |');
        $console->line('| <fg=gray>' . str_pad($lines[1], $boxWidth - 2, ' ', STR_PAD_RIGHT) . '</> |');
        $console->line('| <fg=yellow>' . str_pad($lines[2], $boxWidth - 2, ' ', STR_PAD_RIGHT) . '</> |');

        $console->line($border);
        $console->line('');
    }
}
