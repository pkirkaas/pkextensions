<?php

namespace PkExtensions;

use PkExtensions\Models\PkUploadModel;

//require_once (base_path('/vendor/stefangabos/zebra_image/Zebra_Image.php'));

//use Zebra_Image;
use Illuminate\Http\UploadedFile;
use \Eventviva\ImageResize;
//use PkValidator; #The Facade that is actually a validator factory
use Validator; #The Facade that is actually a validator factory

/**
 * PkFileUploadService - uploads & sanitizes file, returns an array of its
 * properties. 
 *
 * @author pkirk
 */

class PkFileUploadService {
  #Some pre-defined types & corresponding mime-type rules

  public $typearr = [
      'image' => 'file|image',
      'video' => 'file|mimes:ogg,mpeg,mp4,3gpp,webm',
      'audio' => 'file|mimes:mp3,wav,flac',
      'text' => 'file|mimes:txt,html',
      'pdf' => 'file|mimes:pdf',
      'doc' => 'file|mimes:pdf,txt,html',
  ];
  public $path; #If upload succeeds, contains the full real path & name
  public $uploadedFile; #If upload succeeds, contains the uploadedFile instance
  public $reldir; #Can be constructed with a reldir, or passed on upload
  public $typekey; #Can be constructed with a basic type (image, video... as key to typearr
  public $validationStr; #If typekey is a key to typarr, the value. Else, typekey is the rule
  public $resize = [1920,1080,.7]; #Null, or idx array[maxx,maxy,quality] used to resize img where:

  #maxx: maximum pixel width, or none if null
  #maxy: as above
  #quality: between 0-1, converted to appropriate val for jpg or png

  /**
   * 
   * @param string $typekey - expected type of file for validation
   * @param null|string|idx array|assoc array $params -
   *   if string, the $reldir
   *   if idx array, the $resize array as above
   *   if assoc array, keyed: ['reldir'=>$strRelDir,'resize'=>[maxx,maxy,quality]
   * @return file
   */
  public function __construct($typekey = null, $params = null) {
    if (is_array_indexed($params)) {
      $this->resize = $params;
    } else if (is_array_assoc($params)) {
      $this->resize = keyVal('resize', $params);
      $this->reldir = keyVal('reldir');
    } else if (ne_string($params)) {
      $this->reldir = $reldir;
    }
    if (!ne_string($typekey)) {
      return;
    }
    if (in_array($typekey, array_keys($this->typearr))) {
      $this->typekey = $typekey;
      $this->validationStr = $this->typearr[$typekey];
    } else {
      $this->validationStr = $typekey;
    }
  }

  /**
   * 
   * @param string $ctlname - the name of the FILE upload ctl
   * @param string $validationStr - the string of Validation rules for this file
   * @reldir - the relative dir (w. subdirs) to store the uploaded file in, or null
   * @return array ['relpath'=>,  'type', 'mimetype']
   * @throws Validation Exception 
   * 
   */
  public function upload($ctlname, $validationStr = null, $params = null) {

    //pkdebug("in upload - w. ctl [$ctlname], vstr = $validationStr");
    $this->path = $this->uploadedFile = $reldir = $resize = null;
    if (is_array_indexed($params)) {
      $resize = $params;
    } else if (is_array_assoc($params)) {
      $resize = keyVal('resize', $params);
      $reldir = keyVal('reldir');
    } else if (ne_string($params)) {
      $reldir = $reldir;
    }
    if (!ne_string($reldir)) {
      $reldir = $this->reldir;
    }
    if (!ne_string($validationStr)) {
      $validationStr = $this->validationStr;
    }
    if (!$resize) {
      $resize = $this->resize;
    }
    if (ne_string($reldir)) {
      $reldir = pktrailingslashit(pkleadingslashit($reldir));
    } else {
      $reldir = '';
    }
    $request = request();
    $this->uploadedFile = $request->file($ctlname);
    if (!$this->uploadedFile instanceOf UploadedFile || !$this->uploadedFile->isValid()) {
      //pkdebug("UploadedFile: ", $uploadedFile);
      return false;
    }
    //pkdebug("in upload - w. ctl [$ctlname], vstr = $validationStr");
    if ($validationStr) {
      $validator = Validator::make($request->all(), [$ctlname => $validationStr]);
      $validator->validate();
      //PkValidator::validate($request,[$ctlname=>$validationStr]);
    }
    $this->path = base_path('storage/app/' . $this->uploadedFile->store('public' . $reldir));
    if ((PkUploadModel::mimeMainType($this->uploadedFile->getMimeType()) === 'image') && $this->resize) {
      $this->resize($resize);
    }
    $ret = ['relpath' => $reldir . basename($this->path),
        'mimetype' => $this->uploadedFile->getMimeType(),
        'type' => $this->typekey,
    ];
    return $ret;
  }

  /** Simple - keep the image size within boundries (but don't grow), & re-encode
   * .jpeg & .png files to compress better
   * 
   * @param array $resize - [maxx, maxy, quality]
   *   quality optional, between 0-1, only for jpeg or png, resizing itself only
   *   for jpg, png or gif.
   * Both Zebra & ImageResizer work fine - just picked Resizer for now.
   * @throws PkException
   */
  public function resize($resize) {
    $mimetype = $this->uploadedFile->getMimeType();
    $resizableMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($mimetype, $resizableMimeTypes, 1)) {
      pkdebug("The mimetype is not resizable; return");
      return $this->path;
    }
    if ($this->is_animated_gif()) {
      pkdebug("Got an animated GIF");
      return $this->path;
    }
    $maxx = to_int(keyVal(0, $resize, 0));
    $maxy = to_int(keyVal(1, $resize, 0));
    if (!$maxx && !$maxy) {
      pkdebug("No resize dimensions");
      return $this->path;
    }
    $quality = keyVal(2, $resize);
    if (($quality && !is_numeric($quality)) || ($quality < 0) || ($quality > 1)) {
      throw new PkException(["Quality  must be 0 < quality <= 1, but is:", $quality]);
    }
    if (!is_array_indexed($resize)) {
      throw new PkException(["Wrong type of resize:", $resize]);
    }
    #Normalize quality for valid quality range for jpg (1-100) or png (1-9)
    $path = $this->path;
    /*
    $obasename = basename($path);
    $zbasename = "zebra-$obasename";
    $irbasename = "ImgRsz-$obasename";
    $destdir = storage_path('logs');
    $zout = "$destdir/$zbasename";
    $irout = "$destdir/$irbasename";
    #Now lets compare Z & IR:
    //$zebra = new Zebra_Image();
    pkdebug("Path: [$path], ZOUT: [$zout], IROUT: [$irout]");
     */
    $ir = new ImageResize($path);
    if ($quality) {
      if ($mimetype === 'image/png') {
        $quality = intval($quality * 9);
        //$zebra->png_compression = $quality;
        $ir->quality_png = $quality;
      } else if (($mimetype === 'image/jpg') || ($mimetype === 'image/jpeg')) {
        $quality = intval($quality * 100);
        //$zebra->jpeg_quality = $quality;
        $ir->quality_jpg = $quality;
      } else {
        $quality = null;
      }
    }


    #Now, what library do we use? 
    #Try Zebra https://github.com/stefangabos/Zebra_Image   & 
    # ImageResize https://github.com/eventviva/php-image-resize
    #Initialize Zebra:
    //$zebra->source_path = $path;
    //$zebra->target_path = $zout;
    //$zebra->enlarge_smaller_images = false;
    //$zebra->resize($maxx, $maxy, ZEBRA_IMAGE_NOT_BOXED);

    #Now IR:
    if (!$maxx) {
      $ir->resizeToHeight($maxy);
    } else if (!$maxy) {
      $ir->resizeToWidth($maxx);
    } else {
      $ir->resizeToBestFit($maxx, $maxy);
    }
    //$ir->save($irout);

    $ir->save($path);
    return $path;
  }

  /**
   * Detects animated GIF from given file pointer resource or filename.
   *
   * @param resource|string $file File pointer resource or filename
   * @return bool
   */
  public function is_animated_gif($file = null) {
    if (!$file) {
      if (!$this->uploadedFile->getMimeType() === 'image/gif') {
        return false;
      }
      $file = $this->path;
    } else {
      if (mime_content_type($file) !== 'image/gif') {
        return false;
      }
    }
    $fp = null;
    if (is_string($file)) {
      $fp = fopen($file, "rb");
    } else {
      $fp = $file;
      /* Make sure that we are at the beginning of the file */
      fseek($fp, 0);
    }
    if (fread($fp, 3) !== "GIF") {
      fclose($fp);
      return false;
    }
    $frames = 0;
    while (!feof($fp) && $frames < 2) {
      if (fread($fp, 1) === "\x00") {
        /* Some of the animated GIFs do not contain graphic control extension (starts with 21 f9) */
        if (fread($fp, 1) === "\x2c" || fread($fp, 2) === "\x21\xf9") {
          $frames++;
        }
      }
    }
    fclose($fp);
    return $frames > 1;
  }
}
