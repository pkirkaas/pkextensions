<?php
/** (C) Copyright 2018 by Paul Kirkaas. All Rights Reserved */
namespace PkExtensions\Providers;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\MigrationServiceProvider;
use PkExtensions\Console\Commands\MigrateMakeVariant;
use PkExtensions\Console\Commands\MigrateVariant;
use PkExtensions\Console\Commands\Support\MigrationCreatorVariant;
use PkExtensions\Console\Commands\Support\MigratorVariant;
/** * Description of MigrationVariantServiceProvider * */
class MigrationVariantServiceProvider extends MigrationServiceProvider{
  /** * Register the migration creator.  * * @return void */
  /*
  protected $commands = [
      'MigrateVariant' =>'command.migratevariant',
      'MigrateVariantMake'=>'command.migratevariant.make'
      ];
   * 
   */

  public function register() {

    /*
    //parent::register();
$this->app->singleton(\PkExtensions\Commands\Support\MigratorVariant::class, function ($app) {
    return $app['migratorvariant'];
});
     * 
     */
  //  $this->registerMigratorVariantByClass();
    //$this->registerRepositoryVariant();
    $this->registerCreatorVariant();
    $this->registerMigratorVariant();
    //$this->registerMigrateVariantCommand();
    //$this->registerMigrateMakeVariantCommand();
    //$this->commands(array_values($this->commands));
  }

  /*
  public function registerMigrateVariantCommand() {
        $this->app->singleton('command.migratevariant', function ($app) {
            return new MigrateVariant($app['migratorvariant']);
        });
  }
    public function registerMigrateMakeVariantCommand() {
        $this->app->singleton('command.migratevariant.make', function ($app) {
          $creator=$app['migration.creatorvariant'];
          return new MigrateMakeVariant($creator,$app['composer']);
    });
    }
   * 
   */

    /*
    protected function registerRepositoryVariant() {
        //$this->app->singleton('migrationvariant.repository', function ($app) {
        $this->app->singleton(Illuminate\Database\Migrations\DatabaseMigrationRepository::class, function ($app) {
          $table = $app['config']['database.migrations'];
          return new Illuminate\Database\Migrations\DatabaseMigrationRepository($app['db'], $table);
        });
    }
     * 
     */

  protected function registerCreatorVariant() {
      $this->app->singleton('migration.creatorvariant', function ($app) {
          return new MigrationCreatorVariant($app['files']);
      });
  }

  protected function registerMigratorVariant() {
        // The migrator is responsible for actually running and rollback the migration
        // files in the application. We'll pass in our database connection resolver
        // so the migrator can resolve any of these connections when it needs to.
     //$this->app->singleton('migratorvariant', function ($app) {
     $this->app->singleton('migratorvariant', function ($app) {
        //$repository = $app['migrationvariant.repository'];
        $repository = $app['migration.repository'];
      return new MigratorVariant($repository, $app['db'], $app['files']);
    });
  }
    public function provides() {
        return [
            'migratorvarient',  'migration.creatorvariant',
            //'command.migratevariant.make', 'command.migratevariant'
        ];
    }

  
}
