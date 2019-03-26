<?php
namespace PkExtensions\Console;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
/** (C) Copyright 2018 by Paul Kirkaas. All Rights Reserved */
/** *   PkKernal overrides ConsoleKernal, if necesary *
 */
class PkKernel extends ConsoleKernel {
    /** * Get the bootstrap classes for the application.
     * @return array
     */
    protected function bootstrappers() {
        $this->bootstrappers[]=PkBootstrapCommand::class;
        return $this->bootstrappers;
    }
}
