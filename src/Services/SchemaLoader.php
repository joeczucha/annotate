<?php

namespace Howdy\Annotate\Services;

use Illuminate\Support\Facades\DB;

class SchemaLoader
{
    public function getColumns(string $table): array
    {
        $columns = DB::table('information_schema.columns')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->get();

        return collect($columns)->map(fn($col) => [
            'name' => $col->COLUMN_NAME,
            'type' => $col->DATA_TYPE,
            'nullable' => $col->IS_NULLABLE === 'YES',
            'default' => $col->COLUMN_DEFAULT,
            'primary' => $col->COLUMN_KEY === 'PRI',
            'auto_inc' => str_contains($col->EXTRA, 'auto_increment'),
        ])->keyBy('name')->toArray();
    }

    public function getIndexes(string $table): array
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->orderBy('INDEX_NAME')
            ->orderBy('SEQ_IN_INDEX')
            ->get()
            ->toArray();
    }
}
