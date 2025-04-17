<?php

namespace Howdy\Annotate;

use Illuminate\Support\ServiceProvider;

class AnnotateServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            Console\Commands\AnnotateCommand::class,
        ]);
    }
}
