<?php
/** To put the DB name in the Variant Migration Table */
Namespace PkExtensions\Console\Commands\Support;
use Illuminate\Database\Migrations\MigrationCreator;



Class MigrationCreatorVariant extends MigrationCreator {
  public $connection;
  public function create($name, $path, $table = null, $create = false, $connection=null) {
    if (!$connection) {
      $connection = 'variant';
    }
      $this->ensureMigrationDoesntAlreadyExist($name);

      // First we will get the stub file for the migration, which serves as a type
      // of template for the migration. Once we have those we will populate the
      // various place-holders, save the file, and run the post create event.
      $stub = $this->getStub($table, $create);

      $this->files->put(
          $path = $this->getPath($name, $path),
          $this->populateStub($name, $stub, $table,$connection)
      );

      // Next, we will fire any hooks that are supposed to fire after a migration is
      // created. Once that is done we'll be ready to return the full path to the
      // migration file so it can be used however it's needed by the developer.
      $this->firePostCreateHooks($table);

      return $path;
    }

    protected function populateStub($name, $stub, $table, $connection=null) {
      if (!$connection) {
        $connection = 'variant';
      }
      $stub = parent::populateStub($name, $stub, $table);
      $stub = str_replace('DummyConnection', $connection, $stub);
      return $stub;
    }
    public function stubPath() {
        return __DIR__.'/stubs';
    }
}
