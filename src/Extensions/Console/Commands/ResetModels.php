<?php
namespace PkExtensions\Console\Commands;
use Illuminate\Console\Command;
use PkExtensions\Models\PkModel;
class ResetModels extends Command {
    protected $signature = 'reset:models {class?} {modelRoot=\\App\\Models}';

    protected $description = 'For PkModel generated Migrations and Tables,
      drops the underlying table and deletes all the migrations - for the named
      Model, or all if none given - then runs Generate Migrations, Migrate, & DB:SEED.';

    /**
     * Create a new command instance.
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    public function handle() {
      $this->call('destroy:tables');
      $this->call('make:migration');
      $this->call('migrate');
      $this->call('db:seed');
    }
}
