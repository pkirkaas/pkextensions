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
       if (!$class) {
          $classes=\Config::get('app.buildmodels');
          if ($classes && is_array($classes) && count($classes)) {
            foreach ($classes as $class) {
               $this->info ("Starting migration build for: [$class]");
               $class::buildMigrationDefinition();
               $this->info ("Migration building for [$class] done");
            }
           $this->info ("All Migration Builds Completed");

          } else {
            $this->info ("No Models to build Migrations for!");
            die();
          }

       } else {
         $migratontobuild =$modelRoot.'\\'.$class; 
         $this->info ("Starting migration build for: [$migratontobuild]");
         $migratontobuild::buildMigrationDefinition();
         $this->info ("Migration building for [$migratontobuild] done");
       }
    }
}
