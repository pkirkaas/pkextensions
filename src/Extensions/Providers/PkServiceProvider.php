<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */

namespace PkExtensions\Providers;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class PkServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {
      Relation::macro('getModel',function() {
        return $this->getQuery()->getModel();
      });


    }
}
