<?php
/** Just helps build BS4 menus, along with the partials, to keep up with the 
 * changes in one place
 */
namespace PkExtensions;
use Illuminate\Routing\Route;
use \PkRenderer;
use \PkHtml;

/** Mostly builds drop & link li menu items. Like:
 * 
 * 
  echo MenuBuilder::Drop('Reports', [
      ["Insurance Claim", route('report_insuranceclaim'), 'Search & Print Insurance Claims'],
      ["Insurance Arrears", route('report_insurancearrears'), 'Search Unpaid Insurance Claims'],
      ["All Arrears", route('report_arrears'), 'Search Unpaid Client & Insurance Bills'],
      ["Appointment Summary", route('report_appointments'), 'Summary of all your Appointments'],
  ]);
 * 
 * 
  echo MenuBuilder::Link('Schedule',route('user_schedule'));

 */



class PkMenuBuilder {
  public $ps;
  public $menudefaults = [
    'divdrop'  => ['tag'=>'div','class'=>'dropdown-menu'],
    'adrop' => ['tag'=>'a', 'class'=>'dropdown-item'],
    'atoggle' => ['tag'=>'a', 'class'=>
      'nav-link dropdown-toggle site-brand', 'data-toggle'=>'dropdown', 'href'=>'#',
      'role'=>'button','aria-haspopup'=>'true', 'aria-expanded'=>'false'],
    'lidrop' => ['tag'=>'li', 'class'=>"navbar-brand nav-item dropdown site-brand-li"],
    'liflat' => ['tag' => 'li', 'class'=>"navbar-brand nav-item site-brand-li"],
    'aflat' => ['tag'=>'a', 'class'=>"nav-link site-brand tpm-menu-link "],
  ];

  public $depth = 0;
  public $tagstack = [];


  /** Renders a BS4 menu item. $props contains element attributes
   * (which override default), a 'content'=>value (which might be a further rendering),
   * and optionally a "reqs" att array, which are atts added to the default & atts
   * @param array $defaults - the default atts & tag for the type of menu element
   * @param array $props(additional atts , reqs, & content, which might b a further array
   */
  public function _base($defaults= [], $content, $atts=[], $reqs= []) {
    $ps = new PartialSet();
    $ps->arrayseparator ="  ";
    $atts = PkRenderer::cleanAttributes($atts,$defaults,$reqs);
    $tag = unsetret($atts,'tag','a');
    $attstr = PkHtml::attributes($atts);
    $ps[]="<$tag $attstr>";
    $ps[]="  ";
    $ps[]=$content;
    $ps[]="</$tag>\n";
    return $ps;
  }

  /** Makes a single link item drop-down with labels & links
   * 
   * @param string $label - What the top menu li category should be
   * @param arrray $lnkarr array of labels, href, & optatts,
   * [[$label, $href, $optarr],[$label,$href , $optattar]]
   * If $optarr exists & is a string, assumed to be data-tootik
   */
  public static function Drop($label, $lnkarr) {
    $mb = new static();
    $ps = new PartialSet();
    if (is_array($lnkarr)) {
      foreach ($lnkarr as $arr) {
        if (! $arr instanceOf Route) { 
          $atts = keyVal(2,$arr,[]);
          if (ne_string($atts)) {
            $atts = ['data-tootik'=>$atts];
          }
          $atts['href']=$arr[1];
        } else { #It's a route
          $route = $arr;
          $arr = [$route->getAction('desc')];
          $atts = ['href'=>PkHtml::getRouteUrl($route)];
        }
        $ps[]=$mb->adrop($arr[0], $atts);
      }
    }
    return $mb->lidrop([
      $mb->atoggle($label),
      $mb->divdrop($ps)]);
  }

  public static function Link($label, $href=null, $opts=[]) {
    if ($label instanceOf Route) {
      $route = $label;
      $label = $route->getAction('desc');
      $href = $route->getUri();
    }
    if (ne_string($opts)) {
      $opts = ['data-tootik' => $opts];
    }
    $opts['href']=$href;
    $mb = new static();
    return $mb->liflat($mb->aflat($label,$opts));
  }

  public function __call($method,$args) {
    $this->depth++;
    if (!$this->ps) {
      $this->ps = new PartialSet();
    }
    $method = strtolower($method);
    //pkdebug("\nCalling [$method], Depth: ".$this->depth);
    if (in_array($method, array_keys($this->menudefaults) , 1)) {
      array_unshift($args, $this->menudefaults[$method]);
      $res = call_user_func_array([$this,'_base'], $args);
    } else if ($method === 'content') {
      $res = new PartialSet();
      $res[] = $args[0];
    } else {
      throw new PkException("No method [$method]");
    }
    $this->depth--;
    if (!$this->depth) {
      $this->ps[]=$res;
    }
    return $res;
  }

  public function __toString() {
    if (!$this->ps) {
      return '';
    }
    return $this->ps->__toString();
  }
}
