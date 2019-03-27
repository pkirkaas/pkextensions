<?php
/** (C) Copyright 2018 by Paul Kirkaas. All Rights Reserved */
namespace PkExtensions\Providers;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\MigrationServiceProvider;
use PkExtensions\Commands\MigrateMakeVariant;
use PkExtensions\Commands\MigrateVariant;
use PkExtensions\Commands\Support\MigrationCreatorVariant;
use PkExtensions\Commands\Support\MigratorVariant;
/** * Description of MigrationVariantServiceProvider * */
class MigrationVariantServiceProvider extends MigrationServiceProvider{
  /** * Register the migration creator.  * * @return void */

  public function register() {
    //parent::register();
$this->app->singleton(\PkExtensions\Commands\Support\MigratorVariant::class, function ($app) {
    return $app['migratorvarian'];
});
    $this->registerMigratorByClass();
    $this->registerRepository();
    $this->registerCreator();
    $this->registerMigrator();
    $this->registerMigrateVariantCommand();
    $this->registerMigrateMakeVariantCommand();
  }
  public function registerMigratorByClass() {
    $this->app->singleton(\PkExtensions\Commands\Support\MigratorVariant::class, function ($app) {
    return $app['migratorvariant'];
});
  }

  public function registerMigrateVariantCommand() {
        $this->app->singleton('command.migratevariant', function ($app) {
            return new MigrateVariant($app['migratorvariant']);
        });
  }
    public function registerMigrateMakeVariantCommand() {
        $this->app->singleton('command.migratemakevariant', function ($app) {
          return new MigrateMakeVariant($app['migration.creatorvariant'],$app['composer']);
    });
    }

    protected function registerRepository() {
        $this->app->singleton('migrationvariant.repository', function ($app) {
            $table = $app['config']['database.migrations'];
            return new DatabaseMigrationRepository($app['db'], $table);
        });
    }

  protected function registerCreator() {
      $this->app->singleton('migration.creatorvariant', function ($app) {
          return new MigrationCreatorVariant($app['files']);
      });
  }

  protected function registerMigrator() {
        // The migrator is responsible for actually running and rollback the migration
        // files in the application. We'll pass in our database connection resolver
        // so the migrator can resolve any of these connections when it needs to.
     $this->app->singleton('migratorvariant', function ($app) {
        $repository = $app['migrationvariant.repository'];
      return new MigratorVariant($repository, $app['db'], $app['files']);
    });
  }
}
