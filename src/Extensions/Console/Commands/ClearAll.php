<?php
namespace PkExtensions\Console\Commands;
use Illuminate\Console\Command;
class ClearAll extends Command {
    protected $signature = 'wipe';
    protected $description = 'Runs all possible clears and cache dumps';
    public function __construct() {
        parent::__construct();
    }
    public function handle() {
      $this->call('config:cache');
      $this->call('cache:clear');
      $this->call('view:clear');
      $this->call('route:clear');
      $this->call('clear-compiled');
      $this->info("Now run 'composer dump-autoload");
    }
}
