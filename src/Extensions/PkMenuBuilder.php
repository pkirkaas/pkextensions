<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
/** Just helps build BS4 menus, along with the partials, to keep up with the 
 * changes in one place
 */
namespace PkExtensions;
use Illuminate\Routing\Route;
use \PkRenderer;
use PkExtensions\PkHtmlRenderer;
use \PkHtml;
use Illuminate\Support\HtmlString;

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
      #For dropdowns in submenu
    'satoggle' => ['tag'=>'a', 'class'=>
      'nav-link dropdown-toggle submenu', 'data-toggle'=>'dropdown', 'href'=>'#',
      'role'=>'button','aria-haspopup'=>'true', 'aria-expanded'=>'false'],
    'lidrop' => ['tag'=>'li', 'class'=>"navbar-brand nav-item dropdown site-brand-li"],
      #For dropdowns in submenu
    'slidrop' => ['tag'=>'li', 'class'=>" nav-item dropdown submenu"],
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
   * @param array $lnkarr array of labels, href, & optatts,
   * @param $atts array|string - optional atts for top item - if string, assume tootik
   * [[$label, $href, $optarr],[$label,$href , $optattar]]
   * If $optarr exists & is a string, assumed to be data-tootik
   */
  public static function Drop($label, $lnkarr, $optatts=[]) {

    $mb = new static();
    $ps = new PartialSet();
    $submenu = $optatts['submenu'] ?? null;
    if ($submenu) {
      $toggle = 'satoggle';
      $lidrop = 'slidrop';
    } else {
      $toggle = 'atoggle';
      $lidrop = 'lidrop';
    }
    if (is_arrayish($lnkarr)) {
      foreach ($lnkarr as $arr) {
        if ($arr instanceOf Route) { 
          $route = $arr;
          $arr = [$route->getAction('desc')];
          $atts = ['href'=>PkHtml::getRouteUrl($route)];
          $ps[]=$mb->adrop($arr[0], $atts);
        } else if (is_arrayish($arr)) {
          $atts = keyVal(2,$arr,[]);
          if (ne_string($atts)) {
            $atts = ['data-tootik'=>$atts];
          }
          $atts['href']=$arr[1];
          $ps[]=$mb->adrop($arr[0], $atts);
        } else { #It's a stringish link, with everything set
          $ps[]=$arr;
        }
      }
    }
    $optatts = PkRenderer::cleanAttributes($optatts,[],[],'data-tootik');
    return $mb->$lidrop([

      $mb->$toggle($label),
      $mb->divdrop($ps)], $optatts);
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

  // Now do for AJAX menu data
  /**
    Just make single menu list item, with direct link, or dropdown items.
@param $label - string - what it should be called
    @param $links - string for direct link, or else an array, means it's a
      drop-down
    @param $latts - array | string - optional - attributes for the LI - if string, tootik
    @param $aatts - array | string - attributes for the a link 
  **/

/*
  public function alijson($label, $links, $latts = [], $aatts = []) {
    if (is_arrayish($links) { // Have to build dropdown
      $ddlist = new PartialSet();
      foreach ($links as $dditem) { 
        #$dditem array of ['label'=>, 'href'=>, 'atts'=>string|ass arr ]
        $dditem['atts'= keyVal('atts',$dditem,[]);
        if (is_stringish(keyVal('atts',$dditem)) {
          $dditem['atts'] = ['data-tootik' => $dditem['atts']];
        }
        $ddlist[] = $dditem;
      }
        

    }
  }

  public function ajaxdropdown(
  */
}
