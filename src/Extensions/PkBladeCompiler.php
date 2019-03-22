<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
/** For some reason, 5.8 puts the original View path name at the END of the compiled template on my HostGator machine, so override to remove it.

MUST ALSO OVERRIDE ViewServiceProvider as follows:


namespace App\Providers;
use PkExtensions\PkBladeCompiler;
class ViewServiceProvider extends \Illuminate\View\ViewServiceProvider {
    public function registerBladeEngine($resolver) {
        $this->app->singleton('blade.compiler', function () {
            return new PKBladeCompiler(
                $this->app['files'], $this->app['config']['view.compiled']
            );
    });

    $resolver->register('blade', function () {
        return new CompilerEngine($this->app['blade.compiler']);
    });
    }
}







*/
namespace PkExtensions;
use Illuminate\View\Compilers\BladeCompiler;

class PkBladeCompiler extends BladeCompiler {
      /**
     * Compile the view at the given path.
     *
     * @param  string  $path
     * @return void
     */
    public function compile($path = null)
    {
        if ($path) {
            $this->setPath($path);
        }

        if (! is_null($this->cachePath)) {
            $contents = $this->compileString($this->files->get($this->getPath()));

            $this->files->put($this->getCompiledPath($this->getPath()), $contents);
        }
    }


}
