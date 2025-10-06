<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeEnum extends Command
{
    protected $signature = 'make:enum {name}';
    protected $description = 'Create a PHP native enum in app/Enums';

    public function handle()
    {
        $name = Str::studly($this->argument('name'));
        $path = app_path("Enums/{$name}.php");

        if (File::exists($path)) {
            $this->error("Enum {$name} already exists!");
            return Command::FAILURE;
        }

        if (!File::isDirectory(app_path('Enums'))) {
            File::makeDirectory(app_path('Enums'), 0755, true);
        }

        $stub = <<<PHP
<?php

namespace App\Enums;

enum {$name}: string
{
    //
}
PHP;

        File::put($path, $stub);

        $this->info("Enum {$name} created at app/Enums/{$name}.php");

        return Command::SUCCESS;
    }
}
