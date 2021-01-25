<?php

/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */

namespace PkExtensions;
//use Illuminate\Html\HtmlBuilder;
use Collective\Html\HtmlBuilder;

/** NOTE: The Parent Class HtmlBuilder has $this->url - which is NOT a URL,
 * but a URL GENERATOR - so it has access to routes, etc.
 */
class PkHtmlBuilder extends HtmlBuilder
{
  public $name_prefix = null;
  #Allow setting of attribute name prefix globally for builder, or
  # just as an attribute for this single item
  public function attributes($attributes)
  {
    $name_prefix = keyVal('name_prefix', $attributes, $this->name_prefix);
    if ($name_prefix && is_array($attributes) && array_key_exists('name', $attributes)) {
      $attributes['name'] = $name_prefix . '[' . $attributes['name'] . ']';
    }
    if (is_array($attributes)) unset($attributes['name_prefix']);

    /** Automate using BS4 tether Tooltips */
    if (is_array($attributes)) {
      if (array_key_exists('tooltip', $attributes)) {
        $attributes['data-toggle'] = 'tooltip';
        $attributes['title'] = html_encode($attributes['tooltip']);
        unset($attributes['tooltip']);
      }
      if (array_key_exists('tootik', $attributes)) {
        $attributes['data-tootik'] = html_encode($attributes['tootik']);
        unset($attributes['tootik']);
      }
    }
    unset($attributes['']);
    unset($attributes[null]);
    unset($attributes[0]);
    return parent::attributes($attributes);
  }

  //public function image($url, $alt = null, $attributes = [], $secure = null)
  public function image($url, $attributes = null, $secure = [], $alt = null)
  {
    if (ne_string($attributes)) $attributes = ['class' => $attributes];
    if (!is_array($attributes)) $attributes = [];
    if (!$secure) $secure = null;
    return parent::image($url, $alt, $attributes, $secure);
  }

  /** This will use the 'desc' key in the Route declaration as a default title for the link,
   * if $title === false (don't want to override default too much - but see
   * $this->defaultLinkRoute() below)
   * *** NOTE!!!: Modified by adding the $secure parameter to match new HtmlBuilder signature
   * @param type $name
   * @param type $title
   * @param type $parameters
   * @param type $attributes
   * @return type
   */
  public function linkRoute(
    $name,
    $title = false,
    $parameters = [],
    $attributes = [],
    $secure = null,
    $escape = false
  ) {
    if (ne_string($attributes)) {
      $attributes = ['class' => $attributes];
    } else if (!is_arrayish($attributes)) {
      $attributes = [];
    }
    if ($title === false) {
      $escape = false; #The title is coming from the router, trust it
      $title =  keyVal('desc', app()['router']->getRoutes()->getByName($name)->getAction());
    }
    $title = keyVal('img', $attributes, '') . $title;
    $title = keyVal('image', $attributes, '') . $title;
    $escape = keyVal('escape', $attributes, $escape); #So escape can be an argument or attribute
    unset($attributes['img']);
    unset($attributes['escape']);
    unset($attributes['image']);
    pkdebug("In linkRoute - Name: [$name]; params:", $parameters, "URL Type:", typeOf($this->url));
    return $this->link($this->url->route($name, $parameters), $title, $attributes, $secure, $escape);
    //return parent::linkRoute($name,$title,$parameters, $attributes);
  }
  /** This will use the 'desc' key in the Route declaration as a default title for the link,
   * @param string $name - The named link as defined in routes
   * @param array $parameters - parameters to include w. the link
   * @param array|string $attributes - link attributes, class, data-tootik, etc. If str, class
   * @return HTML Link
   */
  public function linkRouteDefault(
    $name,
    $parameters = [],
    $attributes = [],
    $title = null,
    $secure = null,
    $escape = false
  ) {
    if (ne_string($attributes)) {
      $attributes = ['class' => $attributes];
    } else if (!$attributes) {
      $attributes = [];
    }

    //if (!$title) $title =  keyVal('desc',app()['router']->getRoutes()->getByName($name)->getAction());
    if (!$title) $title =  $this->attFromRoute('desc', $name);
    $tootik =  keyVal('data-tootik', $attributes, $this->attFromRoute('tootik', $name));
    if ($tootik) {
      $attributes['data-tootik'] = $tootik;
    }
    return $this->link($this->url->route($name, $parameters), $title, $attributes, $secure, $escape);
    //return parent::linkRoute($name,$title,$parameters,$attributes);
  }

  public function getRouteUrl($route, $params = [])
  {
    return $this->url->route($route->getName(), $params);
  }

  /**
   * If you defined extra attributes in the route definition, get them from name
   * @param string $attName
   * @param string $routeName
   * @param string route attribute value
   */
  public function attFromRoute($attName, $routeName)
  {
    return keyVal($attName, app()['router']->getRoutes()->getByName($routeName)->getAction());
  }
  /*
   * //Remove 11 Nov 16 -- maybe not necessary?
  protected function toHtmlString($html) {
    return $html;
  }
   * 
   */

  /** Generates Bootstrap Dropdown menus - BUT - on click/tourch ONLY IF UA
   * isMobile() - otherwise, on hover. So if onHover, the menu label can be a
   * link as well - if on touch, the item below must be the link.
   * 
   * @param array $items - indexed array of associative arrays of menu items.
   *  Associative Item array:
   * ['label'=>$label,'url'=>$url, {'liclass'=>$liclass, 'aclass'=>$aclass}],
   * where liclass & aclass are optional CSS classes on the item.
   * 
   * NOTE! The first item is required to have an additional key/value:
   * 'alt_label' - If the menu drops down on touch, the first label won't
   *  be linkable - so the first item after it is the label with the link.
   *  So the first item has the additional optional params 'alt_aclass' & 
   * 'alt_liclass'.
   * 
   * @param boolean $noleadinglink - default: false. If true, even a hover 
   * drop down will not have a link at first label.
   * @return HTML string - the dropdown menu.
   */
  public function bsDropMenu(array $items = [], $noleadinglink = false)
  {

    #Drop menu maybe dynamically generated, so might be only 0 or 1 item
    $sz = count($items);
    if (!$sz) return '';
    $menu = '';
    if ($sz === 1) {
      $liclass = keyVal('liclass', $items[0]);
      $url = keyVal('url', $items[0]);
      $aclass = keyVal('aclass', $items[0]);
      $label = keyVal('label', $items[0]);
      $menu .= "<li class='$liclass'><a class='$aclass' href='$url'>
        $label
        </a></li>
      ";
      return $menu;
    }
    foreach ($items as $i => $itemArr) {
      $liclass = keyVal('liclass', $itemArr);
      $url = keyVal('url', $itemArr);
      $aclass = keyVal('aclass', $itemArr);
      $label = keyVal('label', $itemArr);
      $alt_label = keyVal('alt_label', $itemArr);
      $alt_aclass = keyVal('alt_aclass', $itemArr);
      $alt_liclass = keyVal('alt_liclass', $itemArr);
      if ($i === 0) { #It's the first item
        if (isMobile() || $noleadinglink) {
          $droponhover = isMobile() ? '' : ' droponhover ';
          $menu .= "<li class='dropdown $droponhover $liclass'>
            <a href='#' class='$aclass dropdown-toggle' data-toggle='dropdown'>
                  $label
            </a>
            <ul class='dropdown-menu'>
              <li class='$alt_liclass'><a class='$alt_aclass' href='$url'>$alt_label</a></li>
            ";
        } else { #Not mobile - drop on hover
          $menu .= "<li class='dropdown droponhover $liclass'>
            <a class='$aclass' href='$url'>$label</a>
              <ul class='dropdown-menu'>
              ";
        }
      } else { # Not the first item, so all the same now
        $menu .= "<li class='$liclass'><a class='$aclass' href='$url'>$label</a></li>
                ";
      }
    }
    $menu .= "</ul>
      </li>
      ";
    return $menu;
  }
}
