<?php
/** Helper functions for use with Laravel/PkExtensions
 * Depends on function file pklib.php
 * 26 Feb 2016
 * Paul Kirkaas
 */
use PkExtensions\PartialSet;
use Jenssegers\Agent\Agent as MobileDetectAgent;
use Carbon\Carbon;
//use PkLibConfig; #Defined in pklib

#Test update

function setAppLog() {
  PkLibConfig::setSuppressPkDebug(false);
  $logDir = realpath(storage_path().'/logs');
  $logPath = $logDir.'/pkapp.log';
  appLogPath($logPath);
}

/**
 * Returns a user friendly string date format for date string or Carbon date object, with default
 * format or given - BUT also returns "Never" if Carbon date is max/min - or null.
 * @param mixed $date - a date parsable by Carbon, including a Carbon Date object
 * @param string $format
 */
function friendlyCarbonDate($date = null, $format =  'M j, Y') {
  if (!$date) return "Never/None";
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
function pk_showcheck($val = null, $checked = "&#9745;", $unchecked ="&#9744;", $styles = []) {
  $defaultstyles = ['font-size'=>'large','line-height'=>'1rem', 'margin'=>'auto','color'=>'black', 'text-align'=>'center'];
  $stylestr = '';
  foreach ($defaultstyles as $key => $value) {
    $stylestr.="$key:$value; ";
  }
  //$stylestr = implode (';',$defaultstyles);
  if ($val) return "<div style='$stylestr'> $checked </div>";
   return "<div style='$stylestr'>$unchecked</div>";
}

function hpure($input = '') {
  static $htmlpurifier = null;
  if (!$htmlpurifier) {
    $config = HTMLPurifier_Config::createDefault();
    $htmlpurifier = new HTMLPurifier($config);
  }
  return $htmlpurifier->purify($input);
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