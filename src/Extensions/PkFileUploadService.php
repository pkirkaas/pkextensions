<?php
namespace PkExtensions;
use Illuminate\Http\UploadedFile;
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

  public $reldir; #Can be constructed with a reldir, or passed on upload
  public $typekey; #Can be constructed with a basic type (image, video... as key to typearr
  public $validationStr; #If typekey is a key to typarr, the value. Else, typekey is the rule
  public function __construct($typekey=null, $reldir=null) {
    if (ne_string($reldir)) {
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
  public function upload($ctlname,$validationStr = null,$reldir =  null) {
    //pkdebug("in upload - w. ctl [$ctlname], vstr = $validationStr");
    if (!ne_string($reldir)) {
      $reldir = $this->reldir;
    }
    if (!ne_string($validationStr)) {
      $validationStr = $this->validationStr;
    }
    if (ne_string($reldir)) {
      $reldir = pktrailingslashit(pkleadingslashit($reldir));
    } else {
      $reldir = '';
    }
    $request = request();
    $uploadedFile = $request->file($ctlname);
    if (!$uploadedFile instanceOf UploadedFile || !$uploadedFile->isValid()) {
      //pkdebug("UploadedFile: ", $uploadedFile);
      return false;
    }
    //pkdebug("in upload - w. ctl [$ctlname], vstr = $validationStr");
    if ($validationStr) {
      $validator = Validator::make($request->all(),[$ctlname=>$validationStr]);
      $validator->validate();
      //PkValidator::validate($request,[$ctlname=>$validationStr]);
    }
    $path = $uploadedFile->store('public'.$reldir);
    $ret = ['relpath' => $reldir.basename($path),
             'mimetype'=>$uploadedFile->getMimeType(),
             'type'=>$this->typekey,
        ];
    return $ret;
  }
}
