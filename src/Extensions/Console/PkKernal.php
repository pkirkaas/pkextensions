<?php
namespace PkExtensions\Console;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/** (C) Copyright 2018 by Paul Kirkaas. All Rights Reserved */

/**
 * Description of PkKernal
 *
 * @author pkirkaas
 */
class PkKernal extends ConsoleKernel {

    /**
     * Register the given command with the console application.
     *
     * @param  \Symfony\Component\Console\Command\Command  $command
     * @return void
     */
    public function registerCommand($command) {
      echo "In pkkwernal registerCommand\n";
        $this->getArtisan()->add($command);
    }
    /**
     * Run an Artisan console command by name.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @param  \Symfony\Component\Console\Output\OutputInterface  $outputBuffer
     * @return int
     *
     * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
     */
    public function call($command, array $parameters = [], $outputBuffer = null) {
      die("Died in PkKCall") ;
      return "Hello";
      echo "In PkKernal Call\n";
        $this->bootstrap();
        return $this->getArtisan()->call($command, $parameters, $outputBuffer);
    }
    /**
     * Get the bootstrap classes for the application.
     *
     * @return array
     */
    protected function bootstrappers() {
      echo "In PkKernal bootstrappers\n";
        return $this->bootstrappers;
    }

}
