<?php
namespace PkExtensions\Console\Commands;
use Illuminate\Console\Command;
use PkExtensions\Models\PkModel;
class DestroyTables extends Command {
    protected $signature = 'destroy:tables {class?} {modelRoot=\\App\\Models}';

    protected $description = 'For PkModel gemerated Migrations and Tables,
      drops the underlying table and deletes all the migrations - for the named
      Model, or all if none given.';

    /**
     * Create a new command instance.
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    public function handle() {
       $modelRoot = $this->argument("modelRoot");
       $this->info ("Destroying Generated Migration Files and Tables");
       $class = $this->argument("class");
       if (!$class) {
          $classes=\Config::get('app.buildmodels');
          if ($classes && is_array($classes) && count($classes)) {
            foreach ($classes as $class) {
               $this->info ("Deleting Migrations for: [$class]");
               $class::deleteMigrationFiles();
               $this->info ("Migration deletion for [$class] done");
               $this->info ("Dropping table for: [$class]");
               $class::dropTable();
               $this->info ("Table dropped for [$class]");
            }

           #If all classes, drop the Migrations, session & password tables, too.
           $this->info ("Model drops/deletions completed; now the rest:");
           $systables = ['migrations','password_resets','sessions','cache'];
           foreach ($systables as $systable) {
             $this->info ("Dropping $systable...");
             PkModel::dropTable($systable);
           }
          } else {
            $this->info ("No Models to delete for!");
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
