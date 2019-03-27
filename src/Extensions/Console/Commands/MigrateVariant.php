<?php
/** (C) Copyright 2018 by Paul Kirkaas. All Rights Reserved */
/** * Description of MigrateVariant * */
Namespace PkExtensions\Console\Commands;
use PkExtensions\Console\Commands\Support\MigratorVariant;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Migrations\Migrator;
use PkExtensions\Traits\VariantConfigTrait;

class MigrateVariant extends MigrateCommand{
  use VariantConfigTrait;
      protected $signature = 'migratevariant  
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

    public function __construct() {
        $repository = app()['migration.repository'];
      //  pkecho(app()['files']);
       // $this->variantConfig(trim($this->input->getArgument('variant')));
        $mv = new MigratorVariant($repository, app()['db'], app()['files']);
        parent::__construct($mv);
    }

    public function handle() {
       $this->variantConfig(trim($this->input->getArgument('variant')));
       parent::handle();
      
    }

    protected function getMigrationPath() {
      $mpath = parent::getMigrationPath();
      $var =  trim($this->input->getArgument('variant'));
      return $mpath.'/'.$var;
      /*
      return $this->laravel->databasePath().'/'.
       * 
       */
    }
    protected function prepareDatabase() {
      $this->migrator->setConnection('variant');
      
        if (! $this->migrator->repositoryExists()) {
            $this->call('migrate:install', ['variant']);
        }
    }

    /*
    protected function getMigrationPaths() {
    }
     * 
     */
}
