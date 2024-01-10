<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
namespace PkExtensions\Console\Commands;
use Illuminate\Console\Command;
class ClearAll extends Command {
    protected $signature = 'wipe';
    protected $description = 'Runs all possible clears and cache dumps';
    public function handle() {
      $clears = [ 'config:cache', 'cache:clear', 'view:clear', 'route:clear', 'clear-compiled',];
      foreach ($clears as $clear) {
        try {
        $this->call($clear);
        } catch (\Exception $e) {
          $this->info("Clear exception: " . $e->getMessage());
        }
      }

      /*
      $this->call('config:cache');
      $this->call('cache:clear');
      $this->call('view:clear');
      $this->call('route:clear');
      $this->call('clear-compiled');
      */
      /*
      $output = "Pending...";
      $this->exec("composer dump-autoload", $output);
      $this->comment("Dumping Autoload:\n".implode("\n", $output));
      */
      $this->info("Now run 'composer dump-autoload'");
    }
}
