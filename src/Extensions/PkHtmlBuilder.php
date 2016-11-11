<?php

namespace PkExtensions;

//use Illuminate\Html\HtmlBuilder;
use Collective\Html\HtmlBuilder;

class PkHtmlBuilder extends HtmlBuilder {
  public $name_prefix = null;
  #Allow setting of attribute name prefix globally for builder, or
  # just as an attribute for this single item
  public function attributes($attributes) {
    $name_prefix = keyVal('name_prefix',$attributes, $this->name_prefix);
    if ($name_prefix && is_array($attributes) && array_key_exists('name', $attributes)) {
      $attributes['name'] = $name_prefix . '[' . $attributes['name'] . ']';
    }
    if (is_array($attributes)) unset($attributes['name_prefix']);

    /** Automate using BS4 tether Tooltips */
    if (is_array($attributes) && array_key_exists('tooltip', $attributes)) {
      $attributes['data-toggle'] = 'tooltip';
      $attributes['title'] = html_encode($attributes['tooltip']);
      unset ($attributes['tooltip']);
    }
    return parent::attributes($attributes);
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
  public function bsDropMenu(Array $items = [], $noleadinglink=false) {

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
        $menu.= "<li class='$liclass'><a class='$aclass' href='$url'>$label</a></li>
                ";
      }
    }
    $menu .= "</ul>
      </li>
      ";
    return $menu;
  }

}
