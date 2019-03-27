<?php
namespace PkExtensions\Console;
use PkExtensions\Console\Commands\ClearAll;
use PkExtensions\Console\Commands\DestroyTables;
use PkExtensions\Console\Commands\GenerateMigrations;
use PkExtensions\Console\Commands\ResetModels;
use PkExtensions\Console\Commands\MigrateMakeVariant;
use PkExtensions\Console\Commands\MigrateVariant;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
/** (C) Copyright 2018 by Paul Kirkaas. All Rights Reserved */
/** *   PkKernal overrides ConsoleKernal, if necesary *
 */
class PkKernel extends ConsoleKernel {
    protected $commands = [
       GenerateMigrations::class,
       ClearAll::class,
       DestroyTables::class,
       ResetModels::class,
       MigrateMakeVariant::class,
       MigrateVariant::class,
    ];
    /** * Get the bootstrap classes for the application.
     * @return array
     */
    protected function bootstrappers() {
        $this->bootstrappers[]=PkBootstrapCommand::class;
        return $this->bootstrappers;
    }
}
