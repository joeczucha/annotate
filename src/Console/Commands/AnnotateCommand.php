<?php

namespace Howdy\Annotate\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Howdy\Annotate\Services\AnnotationBuilder;
use Howdy\Annotate\Services\AnnotationCleaner;
use Howdy\Annotate\Support\HeaderPrinter;

class AnnotateCommand extends Command
{
    protected $signature = 'annotate';
    protected $description = 'Annotates models with table schema';

    public function handle(AnnotationBuilder $builder, AnnotationCleaner $cleaner)
    {
        $this->intro();

        foreach (File::files(app_path('Models')) as $file) {
            $path = $file->getRealPath();
            $contents = File::get($path);

            $modelClass = 'App\\Models\\' . $file->getFilenameWithoutExtension();

            if (!class_exists($modelClass)) {
                $this->warn("Skipped (missing class): {$file->getFilename()}");
                continue;
            }

            $table = (new $modelClass())->getTable();

            $cleaned = $cleaner->remove($contents);
            $annotation = $builder->build($table);

            $newContents = preg_replace('/<\?php(\s*)/', "<?php$1$annotation", $cleaned, 1);
            File::put($path, $newContents);

            $this->info("Annotated: {$file->getFilename()}");
        }

        $this->outro();
        return Command::SUCCESS;
    }

    protected function intro()
    {
        HeaderPrinter::print($this);

        $this->line("🔍 Annotating your models...\n");
    }

    protected function outro()
    {
        $this->line("\n✅ All done!\n");
    }
}
