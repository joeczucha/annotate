<?php

namespace Howdy\Annotate\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Howdy\Annotate\Services\SchemaLoader;
use Howdy\Annotate\Services\AnnotationBuilder;

class AnnotateCommand extends Command
{
    protected $signature = 'annotate';
    protected $description = 'Annotates models with table schema';

    public function handle(SchemaLoader $loader, AnnotationBuilder $builder)
    {
        $this->intro();

        foreach (File::files(app_path('Models')) as $file) {
            $path = $file->getRealPath();
            $contents = File::get($path);

            if (Str::contains($contents, '/** Schema Information')) {
                $this->info("Already annotated: {$file->getFilename()}");
                continue;
            }

            $modelClass = 'App\\Models\\' . $file->getFilenameWithoutExtension();

            if (!class_exists($modelClass)) {
                $this->warn("Skipped (missing class): {$file->getFilename()}");
                continue;
            }

            $table = (new $modelClass())->getTable();

            $annotation = $builder->build($table);

            $newContents = preg_replace('/<\?php(\s*)/', "<?php$1$annotation", $contents, 1);
            File::put($path, $newContents);

            $this->info("Annotated: {$file->getFilename()}");
        }

        $this->outro();
        return Command::SUCCESS;
    }

    protected function intro()
    {
        $this->line("🔍 Annotating your models...\n");
    }

    protected function outro()
    {
        $this->line("\n✅ All done!\n");
    }
}
