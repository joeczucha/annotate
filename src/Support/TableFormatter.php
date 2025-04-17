<?php

namespace Howdy\Annotate\Support;

class TableFormatter
{
    public function format(array $rows): string
    {
        $colWidths = [];

        foreach ($rows as $row) {
            foreach ($row as $i => $value) {
                $colWidths[$i] = max($colWidths[$i] ?? 10, strlen($value));
            }
        }

        $lines = [];

        foreach ($rows as $row) {
            $line = '';

            foreach ($row as $i => $value) {
                $line .= str_pad($value, $colWidths[$i] + 2);
            }

            $lines[] = ' *  ' . rtrim($line);
        }

        return implode(PHP_EOL, $lines);
    }
}
