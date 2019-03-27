<?php
/** To put the DB name in the Variant Migration Table */
Namespace PkExtensions\Console\Commands\Support;
use Illuminate\Database\Migrations\Migrator;

Class MigratorVariant extends Migrator {
  public function run($paths = [], array $options = []) {
    //pkecho("Paths:",$paths,"options",$options);
    $migrations = parent::run($paths, $options);
    pkecho ("Migrations:", $migrations);
    return $migrations;
  }

  public function getMigrationFiles($paths) {
    //pkecho("Paths:",$paths);
    return parent::getMigrationFiles($paths);
  }
}