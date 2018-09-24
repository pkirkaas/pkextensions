<?php
/** Helper functions for use with Laravel/PkExtensions
 * Depends on function file pklib.php
 * 26 Feb 2016
 * Paul Kirkaas
 */
use PkExtensions\PartialSet;
use PkExtensions\PkExceptionResponsable;
use PkExtensions\Models\PkModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as BaseCollection;
//use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

use Jenssegers\Agent\Agent as MobileDetectAgent;
use Carbon\Carbon;
//use PkLibConfig; #Defined in pklib

/** Tries to return the argument in a pure PHP array, or false.
 * Even tries to execute ->get()->toArray on Eloquent Builder
 */
function eloquentToArray($var) {
  if (is_array($var)) return $var;
  if (!$var) return false;
  if (is_scalar($var)) return false;
  if (($var instanceOf Builder) || ($var instanceOf Relation)) {
    $var = $var->get();
  }
  if ($var instanceOf BaseCollection) return $var->toArray();
  return getAsArray($var);
}

/** Checks $var is an instantiated/persisted (w. ID) instance of PkModel; 
 * false if not, else the class name.
 * @param mixed $var - the value to test
 * @return false|string classname
 */
function pkinst($var) {
  if (PkModel::instantiated($var)) {
    return null;
  }
  return get_class($var);
}

/** Escape for SQL - but ->quote() quotes everything, so have to work around
 * for numerics & dates
 * Problem is, for string table columns that accept empty strings but not null..
 * Enhance later with colType
 */
if ( ! function_exists('esc_sql')) {
  function esc_sql($value, $colType = null) {
    if (is_number($value)) return $value;
    if (typeOf($value) === 'NULL') return 'NULL';
    return app('db')->getPdo()->quote($value);
  }
}
function setAppLog() {
  PkLibConfig::setSuppressPkDebug(false);
  $logDir = realpath(storage_path().'/logs');
  $logPath = $logDir.'/pkapp.log';
  //error_log("AppLogPath: [$logPath]");
  //error_log("AppLogPath: [logPath]");
  appLogPath($logPath);
}

function isLocal($tst = null) {
  static $local = null;
  if($tst !==null) {
    $local = $tst;
  }
  if ($local !== null) {
    return $local;
  }
  return config('app.env') === 'local';
}

function throwerr($msg) {
  throw new PkExceptionResponsable($msg);
}



  /**
 * Returns a user friendly string date format for date string or Carbon date object, with default
 * format or given - BUT also returns "Never" if Carbon date is max/min - or null.
 * @param mixed $date - a date parsable by Carbon,
 *    including a Carbon Date object, OR the string 'now' for the current date.
 * @param string $format
 * @param boolean|string $shownever - what to show for empty/null date?
 *   false: Return empty string
 *   true: Return "Never"
 *   string: return the string
 */
function friendlyCarbonDate($date = null, $format =  'M j, Y',$shownever = false) {
  // American style m/d/y:   'n/j/y'
  if (!$date) {
    if ($shownever===true) {
      return "Never/None";
    } else if (is_string($shownever)) {
      return $shownever;
    } else {
      return '';
    }
  }
  $formats = [
  1 => 'M j, Y', # Apr 24, 2017
  2 => 'M j, y', # Apr 24, 17
  3 => 'n/j/y', # 4/24/17
  4 => 'j-n-y', # 24-4-17
  5 => "F j, Y", # April 24, 2017
  6 => 'M j', # Apr 24
  7 => 'j M', # 24 Apr
  8 => 'j M y', # 24 Apr 17
  9 => 'j M Y', # 24 Apr 2017
  10 => 'j F Y', # 24 April 2017
  11 => 'n/j', # 4/24
];
  if (is_intish($format)) {
    $ky = to_int($format);
    if (in_array($ky,array_keys($formats), 1)) {
      $format = $formats[$ky];
    }
  }

  $date = new Carbon($date);
  $min = Carbon::minValue();
  $max = Carbon::maxValue();
  if ($max->eq($date) || $min->eq($date)) return "Never/None";
  return $date->format($format);
}
/**
 * 
 * @param countable $items
 * @param string $word
 * @return string - count of items, with 'No" for 0, singular for 1, else plural
 */
function numberItems($items,$word) {
  $wordform = str_plural($word);
  if (!$items || !count($items)) {
    $num = "No";
  } else {
    $num = count($items);
    if ($num === 1) $wordform = str_singular($word);
  }
  return "<div class='num-items'>$num </div> <div class='item-desc'>$wordform</div>";
}

##View Helpers
function pk_showcheck($val = null, $styles = [], $checked = "&#9745;", $unchecked ="&#9744;") {
  //pkdebug("Enter w. val", $val, "Styles", $styles);
  if (!ne_string($styles)) {
  $defaultstyles = ['zoom'=>1.5];
    if (is_numeric($styles)) {
      $styles=["zoom"=>$styles];
    }
    if (!is_array_assoc($styles)) {
      $styles = [];
    }
    $styles=array_merge($defaultstyles,$styles);
    $stylestr = '';
    foreach ($styles as $key => $value) {
      $stylestr.="$key:$value; ";
    }
    $styles=$stylestr;
  }
  //pkdebug("Leave w. Styles", $styles);
  if ($val) return "<span style='$styles'> $checked </span>";
   return "<span style='$styles'>$unchecked</span>";
}

function hpure($input = '') {
  if ($input === null) return null;
  static $htmlpurifier = null;
  if (!$htmlpurifier) {
    $config = HTMLPurifier_Config::createDefault();
    $htmlpurifier = new HTMLPurifier($config);
  }
  return $htmlpurifier->purify($input);
}


    /**
     * Build an HTML attribute string from an array.
     * @param array $attArr
     * @return string
     */
function attArrToString ($attArr = null) {
  if (!ne_array_assoc($attArr)) {
    return '';
  }
  $html = " ";
  foreach ( $attArr as $key => $value) {
              $html .= attPair($key, $value)." ";
  }
  return " $html ";
}
  /**
    * Build a single attribute element.
    * @param string $key
    * @param string $value
    * @return string $key="htmlescaped value"
    */
  function attPair($key, $value) {
    if (!ne_stringish($value) || !is_numeric($value)) {
        return " $key ";
    }
    return " $key =" . html_encode($value,'"').' ';
  }


/**
 * Makes an HTML dom element as a string. To  make it faster than the 
 * more elaborate systems. Checks if the tag is a content tag or not,
 * assumes the content has already been cleaned if necessary, applies the
 * attributes, closes it & returns the HTML string.
 * @param sting $tag: the tag - either content or self closing
 * @param string|array|null $aorc1 - if self closing, the attributes of the el.
 *    as an associative array, or If string, class
 * @param string|null $aorc2 - if self closing, ignored. If tag is content tag,
 *   then the atts.
 * @return HTML String
 */
function makeEl($tag,$aorc1=null, $aorc2=null) {
  if (!isTag($tag)) {
    throw new Exception("In makeEl, tag not valid: $tag");
  }
  if (isContentTag($tag)) {
    $content = '';
    if (is_array($aorc1)) {
      $aorc1 = implode("\n",$aorc1);
    }
    if (is_stringish($aorc1)) {
      $content = $aorc1;
    }
    $atts = $aorc2;
  } else {
    $atts = $aorc1;
  }
  if (ne_string($atts)) {
    $atts = ['class'=>$atts];
  } else if (!is_array_assoc($atts)) {
    $atts = [];
  }
  $retstr = "<$tag ". attArrToString($atts).">"; 
  if (isContentTag($tag)) {
    $retstr.="$content</$tag>";
  }
  return $retstr;
}

function makeStyleLinks($relPaths = null) {
  if (!$relPaths) $relPaths = \Config::get('view.relcsspaths');
  if (is_string($relPaths)) $relPaths = [$relPaths];
  if (!$relPaths) $relPaths = [];
  $linkstr = "\n";
  foreach ($relPaths as $relPath) {
    $linkstr .= "\n
     <link href='".asset($relPath)."' type='text/css' media='all' rel='stylesheet'>
       ";
  }
  return $linkstr;
}

/** Route names should be in a config file, and/or the arg array
 * 
 */
function makeAjaxRoutes($extraroutenames = null) {
  if (is_string($extraroutenames)) $extraroutenames = [$extraroutenames];
  if (!is_array($extraroutenames)) $extraroutenames = [];
  $configroutes =   \Config::get('view.ajaxroutenames');
  if (!$configroutes) $configroutes = [];
  $routes = array_merge($configroutes,$extraroutenames);
  $out = "<script>
    var ajaxroutes = {};
    ";
  foreach ($routes as $route) {
    $out .= "    ajaxroutes.$route = '".route($route)."';\n";
  }
  $out .= "</script>\n";
  return $out;
}

function isMobile() {
  $agent = new MobileDetectAgent(); 
  return ($agent->isMobile() || $agent->isTablet());
}




/** Takes a route, parameter name, array of parameter values, and a closure to
 * generate the labels, then makes a drop down menu, for BootStrap 4
 * @param string $route - a Laravel route name
 * @param string|null $paramName - if the route takes a parameter, this is it
 * @param array $valArr - array of values to be iterated over to generate the 
 *    dropdown menu links. Usuall Model/Objects
 * @param Closure $labelClosure - a closure that generates the label for the 
 *    menu item. Will take the current entry in the $valArr 
 * @param string|null $head - if empty, only makes menu dropdown items - not the
 *    full menu wrapped in a li. But if $head is a string, assumes it to be the
 *    label of the whole menu list, and builds it. Otherwise, they could be concatenated.
 */
function makeDropMenu($route, $paramName = null, $valArr = [], $labelClosure = null, $head = null) {
  $out = new PartialSet();
  if ($head) {
    $out[] = "
          <li class='nav-item dropdown'>
            <a class='nav-link dropdown-toggle' data-toggle='dropdown'
               href='#' role='button' aria-haspopup='true' aria-expanded='false'>
               $head
          </a>
          <div class='dropdown-menu'>
      ";
  }
  foreach ($valArr as $val) {
    $url = route($route,[$paramName=>$val]);
    $label = $labelClosure($val);
    $out[] = "<a class='dropdown-item' href='$url'>$label</a> \n ";
  }

  if ($head) {
    $out[] = "
            </div>
          </li>
          ";
  }
  return $out;
}


/**
 * Returns "Yes" if $arg, "No" if $arg explicitly 0 || false; else ''
 * @param mixed $arg
 * @return string : "Yes", "No", or ""
 */
function yesnonull($arg) {
  if ($arg) return 'Yes';
  if (($arg===false) || ($arg === 0)) return "No";
  return '';
}

## Multiple functions to implement pkview
######  Extend the laravel view helper to include other file types, but 
# using same view syntax
# If want to look for vue html templates, include the vue template path 
# in config/view.php
#########  For . separated view names, look for a real file
function getPossibleViewFiles($name) {
  return array_map(function ($extension) use ($name) {
      return str_replace('.', '/', $name).'.'.$extension;
  }, ['phtml','php', 'blade.php','html']);
}

# So we can do like: 
# include(view_path('shared.edit-profile-components'));
function view_path($name) {
  $paths = config('view.paths');
  //pkdebug("View Paths:", $paths);
  foreach ((array) $paths as $path) {
    foreach (getPossibleViewFiles($name) as $file) {
      if (file_exists($viewPath = $path.'/'.$file)) {
        return $viewPath;
      }
    }
  }
  throw new \Exception("View [$name] not found.");
}

/** NOW can pass parameters!
*/
function pkview($name, $params=[], $print = true ) {
  if (! $file= view_path($name)) {
    return false;
  }
  if ($params && !is_array_assoc($params)) {
    throw new PkException('$params in pkview must be an associative array!');
  }
  $XXX_output = null;
  if (is_array($params)) {
    extract($params);
  }
  ob_start();
  include ($file);
  $XXX_output = ob_get_clean();
  if ($print) {
    echo $XXX_output;
  } else {
    return $XXX_output;
  }
}




###########   For uploaded files, get paths, URLs, defaults, etc

/**
 * Returns a URL based on the filename and Laravel default public upload dir
 * @param string/UploadedFile $filename - the base uploaded filename, or path relative to doc root/storage.
 * @param string $default - the relative URL from root of a default URL if no file
 */





// Move to static PkController method, so can be over-ridden in site specific controllers
function urlFromUploadedFilename($filename, $default = null) {
  if (!$filename) {
    if ($default) {
      $filename = $default;
    } else {
      return null;
    }
  } else {
    $filename = "storage/$filename";
  }
  //$baseName = basename($filename);
  $url = url($filename);
  return $url;
}

  /**
   * Generates a key for a cache
   * @param indexed array $components - model instances, strings,collections
   * @param int $maxlength - the maximum allowed length of the key
   */
function cacheKey($components=[],$maxlength=254) {
  pkdbgtm("Starting CacheKey Calc");
  if (!is_array($components)) {
    $components = [$components];
  }
  $rawkey = '';
  foreach ($components as $component) {
    if ($component instanceOf PkModel) {
      $rawkey.=get_class($component)."Id".$component->id;
    } else if (is_scalar($component)) {
      $rawkey.=$component;
    //} else if (is_array($component)) {
     // $rawkey = json_encode($component);
    } else if ($component instanceOf EloquentCollection) {
      if (count($component)) {
        $compclass = get_class($component[0]);
        $rawkey.="Comp".$compclass;
        foreach ($component as $instance) {
          $rawkey.="CId".$instance->id;
        }
      }
    }
  }
  pkdbgtm("FINISHED CacheKey Calc");
  //pkdebug("RawKey:",$rawkey,"MbStrlen:",mb_strlen($rawkey,'UTF-8'));
  if (mb_strlen($rawkey,'UTF-8') < $maxlength) {
    return $rawkey;
  }
  $key =gzcompress($rawkey); 
  pkdebug("Compressed Key:",$key,"MbStrlen:",mb_strlen($key,'UTF-8'));
  if (mb_strlen($key,'UTF-8') < $maxlength) {
    return $key;
  } 
  return null;
}

function clearDbCached($comp) {
  $key = cacheKey($comp);
  Cache::forget($key);
}

/** Takes a relation callable, executes it & gets value
 * 
 * @param type $comp
 * @param type $val
 * @return boolean
 */
function getRelDbCached($comp,$callable = false) {
  $key = cacheKey($comp);
  if (!$key) {
    return false;
  }
  if (Cache::has($key)) {
    return Cache::get($key);
  }
  if ($callable===false) {
    return false;
  }
  
  if (is_callable($callable)) {
    $val = call_user_func($callable);
    $toCache = $val->getResults();
    Cache::put($key,$toCache,10);
    return $toCache;
  }
}

function getDbDataCached($comp,$callable=false,$min=10) {
  $key = cacheKey($comp);
  pkdbgtm("Got Key $key - fetch from Cache");
  if (!$key) {
    pkdebug("?? NO KEY?");
    return false;
  }
  if (Cache::has($key)) {
    pkdbgtm("Getting CACHED! value for $key");
    $ret =  Cache::get($key);
    pkdbgtm("GOT CACHED! value for $key");
    return $ret;
    //return Cache::get($key);
  }
  pkdbgtm("DID NOT FIND !!! cached value for $key");
  if (is_callable($callable)) {
    $val = call_user_func($callable);
    Cache::put($key,$val,$min);
    return $val;
  }
}



/*
function pkl_uploaded_url($filename, $default=null) {
  if (!$filename) $filename = $default;
    if (!$filename) return null;
  //$baseName = basename($filename);
  $url = url("storage/$filename");
  return $url;
}
 * 
 */

/** Given a filename, returns the default upload path
 * 
 * @param string $filename - the uploaded filename or path, AFTER storage & rename
 * @param boolean $symlink - return the hard path, or the path in the symlink directory
 * default: false; the hard path
 * @return string - the filesystem path, or false if not found.
 */
// Move to static PkController method, so can be over-ridden in site specific controllers
/*
function pkl_uploaded_path($filename, $symlink = false) {
  if (!$filename) return false;
  if ($symlink) {
    $path = base_path("/public/storage/$filename");
  } else {
    $path = storage_path($filename);
  }
  if (file_exists($path)) return $path;
  return false;

}
 * 
 */
