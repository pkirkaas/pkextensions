<?php
namespace PkExtensions;
use Imagine\Imagine;
use Imagine\Gd\Imagine as GdImagine;
use Imagine\Image;
use Imagine\Image\Box;
use PkExtensions\PkException;
/**
 * Class to add media manipulation functionality to existing media tools,
 * like Imagine/Imagine
 * @author Paul Kirkaas
 */

Class PkMediaHelper {

  /** Relative path from "public" which contains the subdirs "ORIG_MEDIA", etc
   * 
   */
  public $relpath = 'uploads';
  public $relurl; # Relative URL from '/'. If not set, defaults to $this->relpath;
  public $srcdir = 'ORIG_MEDIA';
  public $cmpdirs;#If empty, defaults to $this->srcdir-$maxwidth-$maxheight for each compression level
      #If you want to specify dirs, do as array, like ['web_compressed','thumbnails',..] etc.
  public $maxwidtharr = [800,400]; //Max width for compressed image & thumbnail image. Can initialize to more levels
  public $maxheightarr; #If not given, defaults to maxwidtharr;
  public $type = 'image';
  public $maxlevels = 0;
  public $img_items;
  public $datarr;

  protected $fullcmpdirs = [];
  protected $cmpurlroots = [];

  public function __contstruct($opts = []) {
    $this->setInstanceAtts($opts);
  }

  public function setInstanceAtts($opts) {
    $res = setInstanceAtts($opts);
    /** Reset because of new options */
    $this->maxlevels=0;
    $this->img_items = null;
    return $res;
  }


  
  /** Builds an array of image information, and optionally creates compressed versions of them
   * Checks if the compressed versions exist before re-compressing them. Returns something like:
   * array: [
  84-Boracay-OurHumbleHomeAfterTyphoon=>[
    mimeType=>string:{image/jpeg}
    file_name=>string:{84-Boracay-OurHumbleHomeAfterTyphoon.jpg}
    full_path=>string:{C:\www\Laravels\lkirkaas.local\laravel\public\uploads/ORIG_MEDIA/84-Boracay-OurHumbleHomeAfterTyphoon.jpg}
    url=>string:{http://lkirkaas.local/uploads/ORIG_MEDIA/84-Boracay-OurHumbleHomeAfterTyphoon.jpg}
    aspect_ratio=>double:{1.5450755601876}
    root_name=>string:{84-Boracay-OurHumbleHomeAfterTyphoon}
    description=>string:{Our Humble Cottage on Boracay, after the Typhoon}
    mimeType_c0=>string:{image/jpeg}
    file_name_c0=>string:{84-Boracay-OurHumbleHomeAfterTyphoon.jpg}
    full_path_c0=>string:{C:\www\Laravels\lkirkaas.local\laravel\public\uploads/ORIG_MEDIA-1000-1000//84-Boracay-OurHumbleHomeAfterTyphoon.jpg}
    url_c0=>string:{http://lkirkaas.local/uploads/ORIG_MEDIA-1000-1000/84-Boracay-OurHumbleHomeAfterTyphoon.jpg}
    mimeType_c1=>string:{image/jpeg}
    file_name_c1=>string:{84-Boracay-OurHumbleHomeAfterTyphoon.jpg}
    full_path_c1=>string:{C:\www\Laravels\lkirkaas.local\laravel\public\uploads/ORIG_MEDIA-256-256//84-Boracay-OurHumbleHomeAfterTyphoon.jpg}
    url_c1=>string:{http://lkirkaas.local/uploads/ORIG_MEDIA-256-256/84-Boracay-OurHumbleHomeAfterTyphoon.jpg}
  ]
  84-Burma-SchwedegonMonk=>[
 
   * @param int levels=2: How many 'levels' of compressed images. Can be as many as elements in $this->maxwidtharr 
   */
  public function buildGalleryArray($levels = null) {
    if ($levels === null) $levels = 2;
    if ($levels > count($this->maxwidtharr)) {
      throw new PkException("Too many levels for array of sizes");
    }
    if (($this->maxlevels >= $levels) && $this->img_items) return $this->img_items; // We've already run; return
    $this->maxlevels = max($this->maxlevels, $levels);
    if (!$this->relurl) $this->relurl = $this->relpath;
    if (!$this->maxheightarr) $this->maxheightarr = $this->maxwidtharr;
    $setpath = realpath(public_path()."/{$this->relpath}").'/';
    $srcdir = $this->srcdir;
    $fullsrcdir = $setpath.$srcdir.'/';
    if (!is_dir($fullsrcdir)) throw new PkException("The src dir [$fullsrcdir] doesn't exist");
    $seturl = url("/").'/'.$this->relurl."/";
    $origurl = $seturl.$srcdir;
    //$levelroots = [];
    $level_items = [];
    for ($level=0; $level < $levels ;  $level++) {
      if (!(keyVal($level,$this->cmpdirs))) {
        $this->cmpdirs[$level] = $srcdir.'-'.$this->maxwidtharr[$level].'-'.$this->maxheightarr[$level];
      }
      $reldir = keyVal($level,$this->cmpdirs);
      $level_items[$level]=[];
      $tmpfullcmpdir =  $setpath.$reldir.'/';
      $this->fullcmpdirs[$level] = $tmpfullcmpdir;
      if (!is_dir($tmpfullcmpdir)) mkdir($tmpfullcmpdir, 0777, true);

      $this->cmpurlroots[$level] = $seturl.$reldir.'/';
      $levelentries = scandir($this->fullcmpdirs[$level]);
      foreach ($levelentries as $entry) {
        
        $fullpath = $this->fullcmpdirs[$level].'/'.$entry;
        $root_name = substr($entry, 0, strrpos($entry, "."));
        if (!($level_item = $this->makeLevelItemArray($level,$fullpath))) continue;
        $level_items[$level][$root_name] = $level_item;
      }
    }
    #Get all the valid image files in the src dir:
    $entries = scandir($fullsrcdir);
    $img_items = [];
    foreach ($entries as $entry) {
      $fullpath = $fullsrcdir.$entry;
      if (!$mimeType = isValidImagePath($fullpath)) continue;
      $root_name = substr($entry, 0, strrpos($entry, "."));
      $exif = exif_read_data($fullpath);
      $img_item = [
        'mimeType' => $mimeType,
        'file_name' =>$entry,
        'full_path' => $fullpath,
        'url' => $origurl.'/'.$entry,
        'aspect_ratio'=>aspectRatio($fullpath),
        'root_name' => $root_name,
        'description'=> keyVal('ImageDescription',$exif),
        ];
      for ($level=0; $level < $levels ;  $level++) {
        //pkdebug("Level: [$level], level_items:", $level_items);
        if (!in_array($root_name, array_keys($level_items[$level]), 1) ) {
           $level_items[$level][$root_name] = $this->makeLevelItemArray($level,$this->makeReducedImageFile($fullpath,$level));
        } 
        $img_item = array_merge($img_item,$level_items[$level][$root_name]);
      }
      $img_items[$root_name] = $img_item;
    }
    return $this->img_items = $img_items;
  }

  protected function makeLevelItemArray ($level, $fullpath) {
        if (!$mimeType = isValidImagePath($fullpath)) return false;
        $filename = basename($fullpath);
        //$reldir = keyVal($level,$this->cmpdirs);
        return [
            "mimeType_c$level"=>$mimeType, 
            "file_name_c$level" =>$filename,
            "full_path_c$level" => $fullpath,
            "url_c$level" => $this->cmpurlroots[$level].$filename,
        ];
  }


  /** 
   * @param int $gba=0: if > 0, sorts output array in groups of size
   *  $groupByAspectRatio sets of AspectRatio >=1, AspectRatio <=1
   * That is, every image in a given row will have an aspect ratio of >1
   * or < 1, but the rows will be mixed.
   * @return array of assoc array of image details, including URLs
   */
  public function groupByAspectRatio($gba = null, $levels = null) {
    $items = $this->buildGalleryArray($levels);
    if (!$gba) return $items;
    $grpd = []; #Reordered array
    $grpw = []; # AR Wider
    $grph = []; # AR Higher
    $grpz = []; # Unknown AR
    foreach ($items as $baseName => $image) {
      $ar = $image['aspect_ratio'];
      if ($ar && ($ar >= 1)) {
        $grpw[$baseName] = $image;
        if (count($grpw) >= $gba) {
          $grpd = array_merge($grpd, $grpw);
          $grpw = [];
        }
      } else if ($ar && ($ar <= 1)) {
        $grph[$baseName] = $image;
        if (count($grph) >= $gba) {
          $grpd = array_merge($grpd, $grph);
          $grph = [];
        }
      } else {
        $grpz[$baseName] = $image;
      }
    }
    #Add the rest that didn't finish a row or have ARs
    $grpd = array_merge($grpd,$grph,$grpw,$grpz);
    return $grpd;

  }

  public function makeReducedImageFile($fullpath,$level) {
    static $imagine;
    if (!$imagine) $imagine = new GdImagine(); 
    $srcImg = $imagine->open($fullpath);
    $filename = basename($fullpath);
    $base_name = substr($filename, 0, strrpos($filename, "."));
    $maxBox = new Box($this->maxwidtharr[$level], $this->maxheightarr[$level]);
    $newfilepath = $this->fullcmpdirs[$level].$base_name.'.jpg';
    $img = static::shrinkWithin($maxBox,$srcImg);
    $img->save($newfilepath);
    return $newfilepath;
  }






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
//    $typeD = typeOf($dim);
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

