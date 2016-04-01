<?php
namespace PkExtensions\Console\Commands;
use Illuminate\Console\Command;
//use App\Models\QProfile;
class GenerateMigrations extends Command {
    protected $signature = 'make:migration {class?} {modelRoot=\\App\\Models}';

    protected $description = 'Allows Models implementing PkModel to specify their own DB attributes in their code, then generate Create and Update Migration tables for the DB';

    /**
     * Create a new command instance.
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    public function handle() {
       $modelRoot = $this->argument("modelRoot");
       $this->info ("Generating Migration Create/Update class");
       $class = $this->argument("class");

       $migratontobuild =$modelRoot.'\\'.$class; 
       $migratontobuild::buildMigrationDefinition();
       $this->info ("Migration building for [$migratontobuild] done");
    }
}
