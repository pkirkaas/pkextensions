<?php
namespace PkExtensions;
use Imagine\Imagine;
use Imagine\Image;
use Imagine\Image\Box;
/**
 * Class to add media manipulation functionality to existing media tools,
 * like Imagine/Imagine
 * @author Paul Kirkaas
 */

Class PkMediaHelper {


  #Image/Box manipulation functions - move them later
  /** Shrink the $obj (if necessary) so it is contained within $dim
   * 
   * @param int|Box|Image $dim - if $dim is 'Square' (Image or Box), $obj will
   * be reduced so it fits entirely within the $dim object. 
   * If $dim > 0, it is the max Height; if < 0, Max Width
   * @param Box|Image $obj - the thing to be resized (if necessary). 
   * @return Box|Image - the possible scaled down version of $obj
   */

  public static function shrinkWithin($dim, $obj) {
    if (!( $ibox = static::boxFromDim($obj, true))) return false;
    if (!( $obox = static::boxFromDim($dim))) return false;
    $iohtrat = $ibox->getHeight() / $obox->getHeight();
    $iowdrat = $ibox->getWidth() / $obox->getWidth();
    $rat = max($iowdrat, $iohtrat);
    if ($rat < 1) return $obj;
    $newBox = $ibox->scale(1 / $rat);
    //if ($obj instanceOf Image) return $obj->resize($newBox);
    if (is_object($obj) && method_exists($obj, 'resize'))
        return $obj->resize($newBox);
    return $newBox;
  }

  /* Makes a Box from an Image or Box or Int (unless $strict)
   * @param Int|Box|Image $dim - the thing to turn into a box
   * If is_int($dim) & $dim < 0, it's the max height; 
   * @param boolean $strict - if true, don't 
   */

  public static function boxFromDim($dim, $strict = false) {
    $typeD = typeOf($dim);
    //pkdebug("DIM IS: [ $typeD ]");
    //if (! $dim instanceOf Image)
    $infinity = 100000;
    if ($dim instanceOf Box) return $dim;
    //if ($dim instanceOf Image) return $dim->getSize();
    if (is_object($dim) && method_exists($dim, 'getsize'))
        return $dim->getSize();
    if ($strict) return false;
    if ($dim < 0) return new Box(-$dim, $infinity);
    if ($dim > 0) return new Box($infinity, $dim);
    return false;
  }

}

