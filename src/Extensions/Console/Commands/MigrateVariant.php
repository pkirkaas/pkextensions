<?php
/** (C) Copyright 2018 by Paul Kirkaas. All Rights Reserved */
/** * Description of MigrateVariant * */
Namespace PkExtensions\Console\Commands;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use PkExtensions\Traits\VariantConfigTrait;

class MigrateVariant extends MigrateCommand{
  use VariantConfigTrait;
      protected $signature = 'migrate  
                {variant : The variant type/key to use to customize the configuration}
                {--database= : The database connection to use}
                {--force : Force the operation to run when in production}
                {--path= : The path to the migrations files to be executed}
                {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
                {--pretend : Dump the SQL queries that would be run}
                {--seed : Indicates if the seed task should be re-run}
                {--step : Force the migrations to be run so they can be rolled back individually}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the Variant database  migrations';

    protected function getMigrationPath() {
      return $this->laravel->databasePath().'/'.
        trim($this->input->getArgument('variant')).'/'.'migrations';
    }
}
