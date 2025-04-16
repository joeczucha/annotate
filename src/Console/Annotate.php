<?php

namespace Howdy\Annotate\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Annotate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'annotate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Annotates models with table schema';

    private $database;
    private $columnData = [];
    private $indexes = [];

    public function __construct()
    {
        $this->database = DB::getDatabaseName();

        parent::__construct();
    }

    private function formatAlignedTable(array $rows): string
    {
        $colWidths = [];

        foreach ($rows as $row) {
            foreach ($row as $i => $value) {
                $colWidths[$i] = max($colWidths[$i] ?? 0, strlen($value));
            }
        }

        $lines = [];

        foreach ($rows as $row) {
            $lineParts = [];

            foreach ($colWidths as $i => $width) {
                $cell = $row[$i] ?? ''; // Use empty string if missing
                $lineParts[] = str_pad($cell, $width + 2); // 2-space padding
            }

            $lines[] = rtrim(implode('', $lineParts));
        }

        // return implode(PHP_EOL, $lines);
        return implode(PHP_EOL, array_map(fn($line) => ' *  ' . $line, $lines));
    }

    private function loadTableData($table)
    {
        $columns = DB::table('information_schema.columns')
        ->select('COLUMN_NAME', 'DATA_TYPE', 'IS_NULLABLE', 'COLUMN_DEFAULT', 'COLUMN_KEY', 'EXTRA')
        ->where('table_schema', $this->database)
        ->where('table_name', $table)
        ->get();

        $columnData = [];

        foreach ($columns as $column) {
            $row['name'] = $column->COLUMN_NAME;
            $row['type'] = $column->DATA_TYPE;
            $row['nullable'] = $column->IS_NULLABLE === 'YES';
            $row['default'] = var_export($column->COLUMN_DEFAULT, true);
            $row['primary'] = $column->COLUMN_KEY === 'PRI';
            $row['auto_inc'] = str_contains($column->EXTRA, 'auto_increment');

            $columnData[$column->COLUMN_NAME] = $row;
        }

        $this->columnData[$table] = $columnData;

        $indexes = DB::table('information_schema.STATISTICS')
        ->select('INDEX_NAME', 'COLUMN_NAME', 'NON_UNIQUE', 'SEQ_IN_INDEX')
        ->where('TABLE_SCHEMA', $this->database)
        ->where('TABLE_NAME', $table)
        ->orderBy('INDEX_NAME')
        ->orderBy('SEQ_IN_INDEX')
        ->get();

        $this->indexes[$table] = $indexes;
    }

    private function buildRowData($table, $columns)
    {
        foreach ($columns as $column) {
            $name = $this->columnData[$table][$column]['name'];
            $type = $this->columnData[$table][$column]['type'];
            $other = [];

            if (!$this->columnData[$table][$column]['nullable']) {
                array_push($other, 'not null');
            }

            if ($this->columnData[$table][$column]['primary']) {
                array_push($other, 'primary key');
            }

            if ($this->columnData[$table][$column]['auto_inc']) {
                array_push($other, 'auto increment');
            }

            $rows[] = [$name, $type, implode(', ', $other)];
        }

        return $rows;
    }

    private function buildIndexData($table)
    {
        return array_map(function ($index) {
            $name = $index->INDEX_NAME;
            $other = ["({$index->COLUMN_NAME})"];

            if (!$index->NON_UNIQUE) {
                array_push($other, 'UNIQUE');
            }

            return [$name, implode(', ', $other)];
        }, $this->indexes[$table]->toArray());
    }

    private function getColumns($table)
    {
        return Schema::getColumnListing($table);
    }

    private function getTableName($file)
    {
        $filename = $file->getFilenameWithoutExtension();
        $class = 'App\\Models\\' . $filename;

        if (class_exists($class) && is_subclass_of($class, \Illuminate\Database\Eloquent\Model::class)) {
            $table = (new $class())->getTable();

            return $table;
        }
    }

    private function alreadyAnnotated($contents)
    {
        return Str::contains($contents, '/** Schema Information');
    }

    private function annotateFile($file)
    {
        $path = $file->getRealPath();
        $contents = File::get($path);

        if ($this->alreadyAnnotated($contents)) {
            $this->info("Already annotated: {$file->getFilename()}");
            return;
        }

        $table = $this->getTableName($file);

        $this->loadTableData($table);

        $columns = $this->getColumns($table);
        $rows = $this->buildRowData($table, $columns);
        $indexes = $this->buildIndexData($table);

        $columnTable = $this->formatAlignedTable($rows);
        $indexTable = $this->formatAlignedTable($indexes);

        $annotation = <<<TEXT
        /** Schema Information
         *
         * Table name: {$table}
         *
        $columnTable
         *
         * Indexes
         *
        $indexTable
         *
         */\n\n
        TEXT;

        // Insert annotation after <?php
        if (Str::startsWith($contents, '<?php')) {
            $contents = preg_replace(
                '/<\?php(\s*)/i',
                "<?php$1$annotation",
                $contents,
                1
            );

            File::put($path, $contents);
            $this->info("Annotated: {$file->getFilename()}");
        } else {
            $this->warn("Skipped (no <?php): {$file->getFilename()}");
        }
    }

    private function intro()
    {
        echo <<<TEXT

----------------------------------------------------------------------------------------
   _                               _    ___                    _        _
  | |                             | |  / _ \                  | |      | |
  | |     __ _ _ __ __ ___   _____| | / /_\ \_ __  _ __   ___ | |_ __ _| |_ ___  _ __
  | |    / _` | '__/ _` \ \ / / _ \ | |  _  | '_ \| '_ \ / _ \| __/ _` | __/ _ \| '__|
  | |___| (_| | | | (_| |\ V /  __/ | | | | | | | | | | | (_) | || (_| | || (_) | |
  \_____/\__,_|_|  \__,_| \_/ \___|_| \_| |_/_| |_|_| |_|\___/ \__\__,_|\__\___/|_|

                        ...by Joe Czucha, Super sexy web developer @ Agriland Media

----------------------------------------------------------------------------------------


TEXT;
    }

    private function outro()
    {
        echo "\nAll done!\n\n";
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->intro();

        $modelPath = app_path('Models');
        $files = File::files($modelPath);

        foreach ($files as $file) {
            $this->annotateFile($file);
        }

        $this->outro();

        return Command::SUCCESS;
    }
}
