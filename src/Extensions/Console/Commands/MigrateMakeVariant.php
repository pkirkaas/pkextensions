<?php

/** (C) Copyright 2018 by Paul Kirkaas. All Rights Reserved */
/* Makes Migrations NOT to the standard DB, but as specified by the required variant argument */
Namespace PkExtensions\Console\Commands;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use PkExtensions\Traits\VariantConfigTrait;

class MigrateMakeVariant extends MigrateMakeCommand {
  use VariantConfigTrait;

  protected $signature = "make:variantmigration
   {name : The name of the migration}
   {variant : The variant type/key to use to customize the configuration}
   {--create= : The table to be created}
   {--table= : The table to migrate}
   {--path= : The location where the migration file should be created}";

  protected $description = "Create a new migration file, with custom DB config";

   protected function writeMigration($name, $table, $create,$connection = null) {
     if (!$connection) {
       $connection='mysql';
     }
     $this->variantConfig(trim($this->input->getArgument('variant')));
     $file = pathinfo($this->creator->create( $name, 
         $this->getMigrationPath(), $table, $create, $connection
    ), PATHINFO_FILENAME);

      $this->line("<info>Created Migration:</info> {$file}");
    }

    protected function getMigrationPath() {
     return parent::getMigrationPath().'/'.trim($this->input->getArgument('variant'));
    }



}
