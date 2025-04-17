<?php

namespace Howdy\Annotate\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Howdy\Annotate\Services\AnnotationCleaner;
use Howdy\Annotate\Support\HeaderPrinter;

class AnnotateClearCommand extends Command
{
    protected $signature = 'annotate:clear';
    protected $description = 'Removes schema annotations from model files';

    public function handle(AnnotationCleaner $cleaner)
    {
        $this->intro();

        foreach (File::files(app_path('Models')) as $file) {
            $path = $file->getRealPath();
            $contents = File::get($path);

            if (! $cleaner->hasAnnotation($contents)) {
                $this->warn("No annotation to remove: {$file->getFilename()}");
                continue;
            }

            $cleaned = $cleaner->remove($contents);
            File::put($path, $cleaned);

            $this->info("Removed annotation: {$file->getFilename()}");
        }

        $this->outro();
        return Command::SUCCESS;
    }

    protected function intro()
    {
        HeaderPrinter::print($this);

        $this->line("🧹 Clearing model annotations...\n");
    }

    protected function outro()
    {
        $this->line("\n✅ All done!\n");
    }
}
