<?php

namespace Howdy\Annotate\Services;

use Howdy\Annotate\Support\TableFormatter;

class AnnotationBuilder
{
    public function __construct(
        protected SchemaLoader $loader,
        protected TableFormatter $formatter
    ) {
    }

    public function build(string $table): string
    {
        $columns = $this->loader->getColumns($table);
        $indexes = $this->loader->getIndexes($table);

        $columnRows = collect($columns)->map(function ($col) {
            $flags = [];
            if (!$col['nullable']) {
                $flags[] = 'not null';
            }
            if ($col['primary']) {
                $flags[] = 'primary key';
            }
            if ($col['auto_inc']) {
                $flags[] = 'auto increment';
            }

            return [$col['name'], $col['type'], implode(', ', $flags)];
        });

        $indexRows = array_map(function ($index) {
            $desc = "({$index->COLUMN_NAME})";
            if (!$index->NON_UNIQUE) {
                $desc .= ', UNIQUE';
            }
            return [$index->INDEX_NAME, $desc];
        }, $indexes);

        $columnTable = $this->formatter->format($columnRows->toArray());

        $indexTable = $this->formatter->format($indexRows);

        return <<<TEXT
/** Schema Information
 *
 * Table name: {$table}
 *
{$columnTable}
 *
 * Indexes
 *
{$indexTable}
 *
 */

TEXT;
    }
}
