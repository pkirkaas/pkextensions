<?php
/** Mis-named 'upload' - also includes managing files from other external sources
 * like images from external URLs
 */
namespace PkExtensions;
use PkExtensions\Models\PkUploadModel;
//require_once (base_path('/vendor/stefangabos/zebra_image/Zebra_Image.php'));
//use Zebra_Image;
use Symfony\Component\HttpFoundation\File\File as SymphonyFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\File;
use PkExtensions\PkFile;
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
  public $file; #If upload succeeds, contains the uploadedFile instance
  public $reldir; #Can be constructed with a reldir, or passed on upload
  public $types = "image"; #allowed major types - if false, accepts all
  public $type; #The major type of the file (image, text, whatever
  public $mimetype; #The full mime type (type/subtype) of the file
  public $originalname; #The original name of the file
  public $validationStr;# If Custom validation rule or rules
  public $resize; #= [1920,1080,.7]; #Null, or idx array[maxx,maxy,quality] used to resize img where:

  #maxx: maximum pixel width, or none if null
  #maxy: as above
  #quality: between 0-1, converted to appropriate val for jpg or png

  /**
   * 
   * @param string|array|null $typekeys - 
       expected type or types of file for validation (general types, like 'text',
         'image', 'video' - not full mime types) - if empty, accept all
   * @param null|string|idx array|assoc array $params -
   *   if string, the $reldir
   *   if idx array, the $resize array as above
   *   if assoc array, keyed: ['reldir'=>$strRelDir,'resize'=>[maxx,maxy,quality]
   * @return file
   */
  public function __construct($types = null, $params = null) {
    pkdebug("Entering __construct, FileUploadService");
    if (is_array_indexed($params)) {
      $this->resize = $params;
    } else if (is_array_assoc($params)) {
      $this->resize = keyVal('resize', $params);
      $this->reldir = keyVal('reldir');
    } else if (ne_string($params)) {
      $this->reldir = $params;
    }
    if (ne_string($types)) {
      $types = [$types];
    }
    if (ne_array($types)) {
      $this->types = $types;
    }
    pkdebug("Leaving __construct, FileUploadService");
  }

  /** If the mimetype is 'image/jpeg', the major type is "image"
   */
  public static function majorType($file) {
    if ($file instanceOf SymfonyFile || $file instanceOf UploadedFile) {
      $mimeType = $file->getMimeType();
    } else if (is_file($file)) {
      $mimeType = mime_content_type($file);
    } else {
      return false;
    }
    return explode('/',$mimeType)[0];
  }
  
  /** If the major type of the file is in the list of major types,
   * or if $types is empty, returns the major type of the file.
   * else false
   */
  public static function isType($file, $types=[]) {
    $type = static::majorType($file);
    if (!$types) {
      return $type;
    }
    if (ne_stringish($types)) {
      $types = [$types];
    }
    if (!is_arrayish($types)) {
      return false;
    }
    if (in_array($type, $types,1)) {
      return $type;
    }
    return false;
  }
  public function fetchFromUrl($href,  $validationStr = null, $params = null) {
    $destpath =sys_get_temp_dir().'/'.uniqid("tfr-",1).'.tmp'; 
    $success = copy($href,$destpath);
    if (!$success || !file_exists($destpath)) {
      pkdebug ("Failed to fetch file from [$href] to [$destpath]");
      return false;
    }
    $this->file = new PkFile($destpath);
    return $this->processfile($this->file,$validationStr,$params);
  }

  /**
   * Retrieves the request & tries to upload the file. If types are given, only      
   *allows one of those types, if no type, uploads anything. But still tries to determine
   * the mime type via PHP. 
   * is one of them, if 
   * @param string $ctlname default "file" - the name of the FILE upload ctl
   * @param string $validationStr - the string of Validation rules for this file
   * @reldir - the relative dir (w. subdirs) to store the uploaded file in, or null
   * @return array ['relpath'=>(renamed, with guessed expension), 
       'type', 'mimetype', originalname]
   * @throws Validation Exception 
   * 
   */
  public function upload($ctlname='file', $types = null, $params = null) {
    $request = request();
    pkdebug("Entering upload, FileUploadService, req data:", request()->all());
    $this->file = $request->file($ctlname);
    //if (!$this->file instanceOf UploadedFile || !$this->file->isValid()) {
    if (!(($this->file instanceOf UploadedFile) || ($this->file instanceOf PkFile)) || !$this->file->isValid()) {
      //pkdebug("file: ", $file);
      return false;
    }
    return $this->processfile($this->file,$types,$params);
  }

  public function processfile($file=null, $types = null, $params = null) {
    if ($file) {
      $this->file = $file;
    }
    if ($types) {
      $this->types = $types;
    }
    if (!(($this->file instanceOf UploadedFile) || ($this->file instanceOf PkFile)) // || !$this->file->isValid()
        ) {
      //pkdebug("file: ", $file);
      return false;
    }
    pkdebug("This File: ", $this->file);
    $this->type = static::isType($this->file, $this->types);
    if (!$this->type) {
      return false;
    }
    $this->path =  $reldir = $resize = null;
    if (is_array_assoc($params)) {
      $this->resize = keyVal('resize', $params);
      $this->reldir = keyVal('reldir');
      $this->validationStr = keyVal('validationStr');
    } 
    if (ne_string($this->reldir)) {
      $this->reldir = pktrailingslashit(pkleadingslashit($this->reldir));
    } else {
      $this->reldir = '';
    }
    //pkdebug("in upload - w. ctl [$ctlname], vstr = $validationStr");
    /*
    if ($validationStr) {
      //$validator = Validator::make($request->all(), [$ctlname => $validationStr]);
      //$validator = Validator::make(['file'=>$this->file], ['file' => $validationStr]);
      //$validator->validate();
      //PkValidator::validate($request,[$ctlname=>$validationStr]);
    }
     * *
     */
    $this->path = base_path('storage/app/' .
       $this->file->store('public' . $this->reldir));
    /**
    if ((PkUploadModel::smimeMainType($this->file->getMimeType()) === 'image') && $this->resize) {
      $this->resize($resize);
    }
     * 
     */
    $ret = ['relpath' => $this->reldir . basename($this->path),
        'mimetype' => $this->file->getMimeType(),
        'size'=>$this->file->getSize(),
        'originalname'=>$file->getClientOriginalName(),
        'type' => $this->type,
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
    $mimetype = $this->file->getMimeType();
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
   * @param resource|string $file PkFile pointer resource or filename
   * @return bool
   */
  public function is_animated_gif($file = null) {
    if (!$file) {
      if (!$this->file->getMimeType() === 'image/gif') {
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
