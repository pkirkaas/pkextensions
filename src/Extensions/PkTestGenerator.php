<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
 /** Generates test data - 
  */

namespace PkExtensions;
use PkExtensions\References\ZipRef;


class PkTestGenerator {
#This belongs in a localized class, but will do for now
  public static $comments = [
      'She is doing better than I expected',
      'Very good progress since our previous session',
      "I'm concerned - she seems to be on a downward slope",
      "It's so hard for her to go through this alone, but she's much better off without him",
      "His substance abuse seems to be getting worse",
      "Excellent progress since last time",
      "The family is so poor they can't focus on other important issues",
      "The medication seems to be helping",
      "Her spirit is very good, under the circumstances",
      "We have to keep an eye on her progress and check which way she's going next time",
  ];

  /** Used to hold registered method names, data sources, and rules for using
   * registered reference files to provde sample data
   * @var array 
   */
  public static $externalReferences = [];

  /** The reference might be a file name, or an array. The method name is used
   * as the key to the info array in __staticCall()
   * @param type $reference
   * @param type $methodName
   * @param type $rules
   */
  public static function registerReferences($reference, $methodName, $rules = null) {
    $row = [];
    if (is_arrayish($reference)) $row['cache'] = $reference;
    else if (is_string($reference)) $row['file'] = $reference;
    else throw new \Excepton ("We don't know what to do with the datasource");
    $row['rules'] = $rules;
    static::$externalReferences[$methodName] = $row;
    return;
  }

  /** Basic
   *  
   * @param type $method
   * @param type $args
   * @return type
   * If an undefined starts w randXXX, remove it & see if we have any static
   * variables with XXX. If so, & array, do rand on the array.
   * If XXX is a string - see if a file-name - if so, load it into the var
   * as an array, & do the same
   */
  public static function __callStatic($method, $args) {
    if($var = removeStartStr($method,'rand')) { #Do we have a static var declared 
      $res = static::loadArrayFromFile($var);
      if (is_array($res)) {
        if (is_array($args) && count($args)) {
          return static::randData($res, $args[0]);
        } else {
            return static::randData($res);
        }
      }
    }

    if($var = removeStartStr($method,'all')) { #Do we have a static var declared 
      $res = static::loadArrayFromFile($var);
      if (is_array($res)) return $res;
    }


    $row = static::$externalReferences[$method];
    if (!array_key_exists('cache', $row)) {
      $row['cache'] = include($row['file']);
    }
    switch (count($args)) {
      case 0 : return static::randData($row['cache']);
      case 1 : return static::randData($row['cache'], $args[0]);
    }
  }

  /** Some static data arrays might be huge & we don't want to load them if
   * we don't need them - so they have the path to a PHP file instead. The
   * first time they are called, we include the PHP file & load the array
   */
  public static function loadArrayFromFile($var) {
    $var = strtolower($var);
    if (!property_exists(static::class, $var)) return false;
    if (is_string(static::$$var) && is_file(static::$$var) 
        && (pathinfo(static::$$var,PATHINFO_EXTENSION) === 'php')) {
          static::$$var = include(static::$$var);
    } #Hopefully static $var is now an array
    if (is_array(static::$$var)) return static::$$var;
    return false;
  }

  /** Returns the season (and by defaul Year) from an SQL or Unix date */

  public static function getSeasonFromDate($date, $wyr = 1, $sql = true) {
    if ($sql) $date = strtotime($date);
    if ($wyr) $yr = '  '. date('Y', $date);
    else $yr = '';
    $m = (int)(date('n', $date));
    if (($m < 3) || ($m > 10) ) return 'Winter'.$yr;
    if ($m < 6) return 'Spring'.$yr;
    if ($m < 9 ) return 'Summer'.$yr;
    return 'Fall'.$yr;
  }





  public static function randComment() {
    return static::randData(static::$comments);
  }
  /**
   * Returns random data item (possibly with invalid data item) from an array 
   * @param array $dataArr - source of data - array(ish) is also okay (EG, ArrayObject)
   * @param integer $items - default: -1 - means return a single item. $items >= 0
   *   means return an array, empty for $items = 0...
   * @return single instance or array, depending on num items
   */
  public static function randData( $dataArr, $items=-1, $withKeys = false) {
    try {
      //if (!$items) throw new \Exception("Invalid value or type for items");
      if (!$items) return [];
      if (!is_numeric($items)) throw new \Exception("Invalid value or type for items");
      if (!is_arrayish($dataArr)) throw new \Exception("dataArr not arrayish");
    } catch (\Exception $e) {
      die( $e->getTraceAsString());
    }
    if (is_object($dataArr)) {
      if (method_exists($dataArr,'getArrayCopy')) {
        $copy = $dataArr->getArrayCopy();
      } else if (method_exists($dataArr, 'toArray')) {
        $copy = $dataArr->toArray();
      } else {
        $type = typeOf($dataArr);
        try {
          throw new \Exception("dataArr is type: $type");
        } catch (\Exception $e) {
          die( $e->getTraceAsString());
        }
      }
    }
    else $copy = (array) $dataArr;
    $keys = array_keys($copy);
    $numkeys = count($keys);
    if ($items === -1) {
      $key = $keys[mt_rand(0,  $numkeys - 1)];
      $val = $copy[$key]; 
      if ($withKeys) $val = [$key=>$val];
      return $val;
    }
    #More than one item to return; so an array. Unique menbers
    $retarr = [];
    for ($i = 0 ; $i < min($numkeys, $items) ; $i++) {
      $keysleft = array_keys($copy);
      $numkeysleft = count($keysleft);
      $key = $keysleft[mt_rand(0,  $numkeysleft - 1)];
      if ($withKeys) {
        $retarr[$key] =  $copy[$key];
      } else {
        $retarr[] = $copy[$key];
      }
      unset($copy[$key]);

    }
    return $retarr;
  }


  public static $jobTitles = null;

  public static function randJobTitle() {
    if (static::$jobTitles === null) {
      static::$jobTitles = require(__DIR__.'/References/JobTitles.php');
    }
    return static::randData(static::$jobTitles);
  }

  /** Returns a random SQL Time of Day
   * 
   * @param array $opts:
   *   'from' - hour from: default: 8AM
   *   'to' - hour to: default: 17:00/5PM
   *   'inc' - incremental minutes: default(max): 30. 0 means only whole hours
   */
  public static function randSqlTime($opts=[]){
    $from = keyVal('from', $opts,8);
    $to = keyVal('to', $opts,17);
    $inc = min(keyVal('inc',$opts,15),30);
    $hour = mt_rand($from,$to);
    if (!$inc) return "$hour:00";
    $inc = max($inc, 10);
    $rg = 60/$inc;
    $mins = mt_rand(0,$rg)*$inc%60;
    return "$hour:$mins";

  }


  /**
   * Returns a date within the the range (default: years)
   * @param integer $yrsFrom: Plus or Minus years from now
   * @param integer $yrsTo: Plus or Minus years from now. Default: Now
   * @param integer $multiplier - num days - default, 365, 1 yr
   * @return string: Pretty Date within the range
   */
  public static function randUnixDateFromRange($from, $to=0, $multiplier = 365) {
    $from = $from * $multiplier;
    $to = $to * $multiplier;
    if ($from < $to) {
      $rndOffset = mt_rand($from, $to);
    } else {
      $rndOffset = mt_rand($to, $from);
    }
    $day = 24 * 60 * 60;
    $now = time();
    $rndDayPart = mt_rand(0,$day);
    $rndTime = $now + ($rndOffset * $day) + $rndDayPart;
    return $rndTime;
  }

  /** Get a sql date about n months in the future or past
   * @param boolean $time = false - just the date. If true, DateTime
   */

  public static function randDateOffset($n, $days = 30, $time = false) {
    if ($n < 0 ) $m = $n - 1;
    else $m = $n + 1;
    $m = (int) (1.5 * $m);
    return static::randSqlDateFromRange($n, $m, $days, $time);
  }

  public static function randLorem($len = 0) {
    static $texts = [

        'Earum placerat lorem est nunc id a congue ornare suspendisse lectus elementum',
        'Id qui nulla justo ante phasellus fectus viverra egestas pellentesque pede metus. Habitant et urna. Amet magna libero. Non sed cras. Placerat magna qui. Mauris euismod quo. Amet id purus. Amet eros in lacus libero vitae. Sed quis consectetuer. Aliquam leo donec sagittis in non wisi praesent pharetra nascetur ut torquent. Tortor amet molestie. Sed a at diam integer arcu. In fringilla odio velit auctor amet',
        'feugiat tellus id nec luctus et pede luctus nibh',
        'egestas consectetuer in Purus wisi massa. Integer gravida dui sed elementum neque. Quisque aut ipsum urna pede in et adipiscing aptent. Urna mi qui leo proin ut. Mi mauris elementum libero hendrerit in officiis lobortis ante. Sed mauris amet. Neque nunc eros at quis pellentesque aliquam ipsum libero. Morbi faucibus rhoncus. Est amet viverra. Eget nulla ridiculus. Amet aliquam at. Nec mi cras.',
        'Molestiae quisque odio mattis volutpat ante. Ut dui amet. Donec in nunc risus etiam hendrerit arcu tellus felis massa id soluta est imperdiet metus. Neque sed malesuada. Arcu viverra elementum odio suspendisse diam dui pellentesque metus',
        'hymenaeos auctor tellus. Varius sit odio. Amet morbi tempus mauris gravida libero pede urna ut. Dolores nulla id mauris lorem morbi. Porttitor nam mattis potenti non velit. Fringilla sapien ipsum tortor pede nec sem sem ea. Pulvinar sed vitae sunt libero pellentesque mi in nec. Elit auctor eros dignissim tristique nunc. In lorem eu.',
        'Vestibulum consequat ut cras luctus eu at pede mauris. Nullam a est vitae turpis a commodo sit sit sagittis adipiscing lacinia. Lorem nulla eu. Condimentum cras senectus turpis justo mi facilisi sodales pellentesque. Libero ullamcorper lectus. Mattis lorem elementum. Est mauris id morbi omnis tellus dolor mi enim. Dolor lacinia wisi metus ac rutrum. Integer vitae consequat. Dolor etiam eget.',
    ];

    $str = static::randData($texts);
    if ($len) $str = substr($str, 0, to_int($len));
    return $str;
  }



  /**
   * 
   * @param int $from - num of units from now
   * @param int $to - num of units from now
   * @param int $multiplier - multiply from/to days - default, 365, so from/to are years
   * @param boolean $time - default false -- just an SQL Date, or DateTime?
   * @return type
   */
  public static function rndSqlDtTmFromRange($from, $to=0, $multiplier = 365) {
    return static::randSqlDateFromRange($from, $to, $multiplier, true);
  }
  public static function randSqlDateFromRange($from, $to=0, $multiplier = 365, $time = false) {
    return static::sqlDateFromUnix(static::randUnixDateFromRange($from, $to, $multiplier), $time);
  }

  public static function randSqlRecentDate($multiplier = 365, $time=false) {
    return static::sqlDateFromUnix(static::randUnixDateFromRange(0, -1, $multiplier), $time);
  }

  public static function randSqlBirthdate($multiplier = 365) {
    return static::sqlDateFromUnix(static::randUnixDateFromRange(-20, -70, $multiplier));
  }

  public static function sqlDateFromUnix($unixDate, $time=false) {
    if ($time) $mysqldate = date('Y-m-d H:i:s', $unixDate);
    else $mysqldate = date('Y-m-d', $unixDate);
    return $mysqldate;
  }

  /* Takes actual dates and returns something in between. Takes Unix or SQL,
   * and returns the date in the same format 
   */
  public static function randDateBetween($date1, $date2=null) {
    if (to_int($date1) && (is_null($date2) || to_int($date2))) {
      $sql = false;
      if (!$date2) $date2=time();
    } else if (is_string($date1) && (is_null($date2) || is_string($date2))) {
      $sql = true;
      $date1 = strtotime($date1);
      if (!$date2) $date2=time();
      else $date2 = strtotime($date2);
   } else {
     try {
        throw new \Exception("We don't know how to deal with the arguments");
     } catch (\Exception $e) {
        die( $e->getTraceAsString());
     }
   }
   $min = min($date1, $date2)/1000;
   $max = max($date1, $date2)/1000;
   $rnd = 1000 * mt_rand($min, $max);
   if (!$sql) return $rnd;
   return date( 'Y-m-d H:i:s', $rnd );
  }

  public static function randFullName() {
    return static::randFirstName() . ' ' . static::randLastName();
  }

  public static function randFirstName() {
    $fnames = array('Joe', 'Sally', 'Mary', 'Jose', 'Abdul', 'Katarina', 'Joachim',
        'Xhu', 'Toby', 'Saskia', 'Misha', 'Vaseem', 'Kalleen', 'Kenzie', 'Elizabeth',
        'Justin', 'Taylor', 'Giovanna', 'Stephanie', 'Olivia', 'Scott', 'Tina',
        'Amber', 'Bryan', 'Porche', 'Eric', 'Michelle', 'Ashley', 'Robert',
        'William', 'Emily', 'Caitlin', 'Justin', 'Morgan', 'Gabrielle', 'Deanna',
        'Brian', 'Maxwell', 'Amanda', 'Jessica', 'Sarah', 'Shirby', 'Allison',
        'Heather', 'Sydney', 'Alex',);
    return static::randData($fnames);
  }

  public static function randLastName() {
    $lnames = array('Smith', 'Lee', "O'Brien", "M'Beko", 'Semaphore', 'Crankshaft',
        'Kahn', 'Zaheer', 'Clymatis', 'Wagner-Spiel', 'Van Hoffen Schmidt',
        'Sidhartha', 'Evans', 'Bradbury', 'Loyack', 'Yost', 'Moulton', 'Thompson',
        'Ciurleo', 'Ibarra', 'Reyes', 'Negron', 'Bevis', 'Beard', 'Moyer', 'Davis',
        'Thomas', 'Jadush', 'Stewart', 'Boren', 'Holst', 'Hassler', 'Alvey',
        'Hamilton', 'Suskin', 'Howell', 'Blomquist', 'Cassel', 'Bourjac',
        'Anderson', 'Foye', 'Stenlake', 'Crookham', 'May', 'McLaughlin', 'Peterson',
        'Rivers',);
    return static::randData($lnames);
  }

  public static function randImgUrl() {
  $imgurls = [
    'https://upload.wikimedia.org/wikipedia/commons/thumb/e/ed/D%C3%BClmen%2C_Wildpark_--_2017_--_6075-81.jpg/500px-D%C3%BClmen%2C_Wildpark_--_2017_--_6075-81.jpg',

    'https://commons.wikimedia.org/wiki/Category:Featured_picture_candidate_archives#/media/File:Manadani_peak_after_Bantoli_during_Wikipedia_Treks_Madhyamaheshwar_photo_by_Sumita_Roy.jpg',

    'https://upload.wikimedia.org/wikipedia/commons/3/31/Henryk_Jarzynski_in_Skarzyce.jpg',

    'https://en.wikipedia.org/wiki/User:Mav/images#/media/File:Mojave_suncup_flower_at_the_mouth_of_Titus_Canyon.JPG',

    'https://upload.wikimedia.org/wikipedia/commons/7/7a/Ole_Lippmann.jpg',

    'https://commons.wikimedia.org/wiki/Category:Historical_images#/media/File:OFK_Odzaci_1983-84.jpg',
];
    return static::randData($imgurls);
    }


######################  THIS DOESN'T WORK YET W. LARAVEL - BUT GET IT GOING.....
  /** 'Uploads' a random file from the directory, of 'type'
   * 
   * @param string $path - directory path
   * @param string $type - file object 'type' - 'image', 'audio', 'doc', etc.
   *     default: 'image'
   * @return int - the ID of the newly uploaded file object
   */
  //public static function importRandomFileFromDir($dir, $type = 'image') {
  public static $tmpdir = __DIR__.'/tmp';
  public static function randFilePathFromDir($dir, $type = 'image') {
    pkdebug("DIR:  [$dir] ");
    if (!is_dir($dir)) {
      pkdebug("Couldn't resolve [$dir] to a directory");
      return null;
    }
    $tmpdir = static::$tmpdir;
    if (!is_dir($tmpdir)) {
      mkdir($tmpdir);
    }

    $entries = scandir($dir);
    pkdebug("Entries:  ", $entries);
    if (!is_array($entries) || !sizeOf($entries)) {
      pkdebug("No entries in [$dir]. Entries:", $entries);
      return null;
    }
    #Make array of valid file type paths from entries
    $paths = [];
    //$validentries = [];
    foreach ($entries as $entry) {
      $newpath = pkuntrailingslashit($dir) . "/$entry";
      if (!is_file($newpath)) continue;
     // $validentries[]=$entry;


//      $valid = BaseFileHandler::validateFiletype($newpath, $type);
 //     if ($valid === false) continue;
      $paths[] = $newpath;
    }
    pkdebug("For [$dir], got valid paths:", $paths);
    #We have a set of valid file paths of the required type. Pick one:
    $path = static::randData($paths);
    $base = basename($path);
    $copypath = pkuntrailingslashit(static::$tmpdir)."/$base";
    copy($path,$copypath);
    //Copy it to 
    return $copypath;
    /*
  //  $relPath = BaseFileHandler::relPathFromFullPath($path);
    //pkdebug("Your rel path:", $relPath);
    $mimeType = getFileMimeType($newpath);
    $newFileObjArgs = [
        'mimetype' => $mimeType,
        'path' => $relPath,
        'type' => $type,
    ];

    $fileObj = BaseFileHandler::makeNewFileObj($newFileObjArgs);
    if (!($fileObj instanceOf BaseFile)) return false;
    //pkdebug("Wow - made a file obj!");
    $fileObj->save();
    $fileObjId = $fileObj->getId();
    return $fileObjId;
    #We have copied a file into our upload dir. Now let's make a file obj of it -
    //}
     * 
     */
  }


/** UNFINISHED!
    Copies a random file from $srcdir to default uploads dir
    @param string|null $srcdir - the directory to "upload" from, 
      Default: Base "Sundries" Dir
*/

/*
  public static function randFileToUploads ($srcdir = null, $uploads_reldir = 'uploads') {
    if (! $srcdir) {
      $srcdir = realpath(__DIR__ . "/../../../Sundries");
    }
    if (!is_dir($srcdir)) {
      throw new \Exception("Couldn't resolve [$srcdir] to a directory");
    }
    $filenames = scandir($srcdir);
    $realfiles = [];
    foreach ($filenames as $filename) {
      $filepath = $srcdir.'/'.$filename;
      if (!is_file($filepath)) continue;
      $realfiles[$filename] = $filepath;
    }
    if (!count($realfiles)) return null;
    #Make a default uploads file path
    $uploads_dir = public_path()."/$uploads_reldir/");
    if (!is_dir($uploads_dir)) {
      throw new \Exception("Couldn't resolve [$uploads_dir] to a directory");
    }
#Get a random filename, copy from src dir to uploads dir
    $rndName = static::randData(array_keys($realfiles));


  }
  */




  /** "Uploads" all files from the srcdir, into a 'cache' directory.  
    @param string|null $srcdir - the directory to "upload" from, 
      Default: Base "Sundries" Dir
    @return array - array of Symfony upload details
  */
  /*
  public static function makeUploadedSpfFiles($srcdir=null) {
    if (! $srcdir) {
      $srcdir = realpath(__DIR__ . "/../../../Sundries");
    }
    if (!is_dir($srcdir)) {
      throw new \Exception("Couldn't resolve [$srcdir] to a directory");
    }

    $filenames = scandir($srcdir);
    $symfups = [];
    foreach ($filenames as $filename) {
      $fpath = $srcdir . "/$filename";
      if (is_file($fpath)) {
        $newfpath = __DIR__ . "/cache/$filename";
        copy($fpath, $newfpath);
        $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $newfpath);
        $size = filesize($newfpath);
        $symfups[] = new SymfonyUploadedFile($newfpath, $fpath, $mime, $size, 0, true);
      }
    }
    return $symfups;
  }
  */



  /** REMOVES & returns a random item from the indexed array. 
   * ARRAY PASSED BY REFERENCE, SO SHOULD BE CALLED WITH A COPY!
   * the item from the array passed by reference, so as not to return the same
   * value twice
   * @param arrayish &$dataArr
   * @return type
   */
  public static function removeRandomItem(&$dataArr = []) {
    if (is_array($dataArr)) { #Only on arrays so far
      $dataArr = array_values($dataArr); #Make sure sequentiallly indexed
    }
    if (!sizeOf($dataArr)) return null;
    $randkey = mt_rand(0, sizeof($dataArr) - 1);
    $retval = $dataArr[$randkey];
    if (is_array($dataArr)) {
      unset($dataArr[$randkey]);
      $dataArr = array_values($dataArr);
    }
    return $retval;
  }

  /** Returns a STRING of random digits, 
   * @param $from - minimal initial digit - default 0
   */
  public static function randDigitString($length=1,$from=0) {
    $ret = '';
    for ($i=0 ; $i<$length; $i++) {
      $min = ($i===0) ? $from : 0;
      $digit = mt_rand($min,9);
      $ret .= "$digit";
    }
    return $ret;
  }

  /** Generates a random SSN, 
   * @param string $separator = '' - what separates the components
   * @return string - SSN
   */
  public static function randSSN($separator = '') {
    return static::randDigitString(3).
      $separator.static::randDigitString(2).
      $separator.static::randDigitString(4);
  }

  public static function randPhone($separator = '-') {
    return static::randDigitString(3,2).
      $separator.static::randDigitString(3,2).
      $separator.static::randDigitString(4);
  }



  public static function randFullAddress($separator=',') {
    $faker = \Faker\Factory::create();
    $location = ZipRef::randLocation();
    return $faker->streetAddress."$separator ".
        $location['city']."$separator ".
        $location['state']."$separator ".
        $location['zip'];
  }

#############  To pre-populate supposed user upload files ############

  /**
   * 
   * @param string $fromDir - directory to copy from - usu. in the 'seeds' dir
   * @param string $toDir - the directory to copy to - default is the storage dir
   * @return array - all the filenames successfully copied
   * @throws \Exception
   */
  public static function copyAllFiles($fromDir, $toDir=null) {
    //if (!$toDir) $toDir = storage_path("app/public");
    # 2024 - changed storage_path to public_path
    if (!$toDir) $toDir = public_path("storage");
    if (!is_dir($toDir)) {
      pkwarn("ToDir [$toDir] doesn't exist - creating");
      mkdir($toDir, 0755,  true);
    }
    pkdebug("Copying all files from [$fromDir] to [$toDir]");
    if (!is_dir($fromDir)) throw new \Exception("FromDir [$fromDir] not found");
    if (!is_dir($toDir)) throw new \Exception("ToDir [$toDir] not found");
    $entries = scandir($fromDir);
    $filenames=[];
    foreach ($entries as $entry) {
      if (($entry === '.') || ($entry === '..')) continue;
      $fromPath = "$fromDir/$entry";
      if (!file_exists($fromPath)) throw new \Exception("FromPath [$fromPath] not found");
      if (!copy($fromPath,"$toDir/$entry"))  throw new \Exception("Couldn't copy to [$toDir/$entry]");
      $filenames[] = $entry;
    }
    return $filenames;
  }

  /**
   * Get an array of all file names in a directory
   * @param string $dirName - directory name - defaults to storage dir
   */
  public static function getFilenamesInDir($dirName=null) {
    //if (!$dirName) $dirName = storage_path("app/public");
    # 2024 - changed storage_path to public_path
    if (!$dirName) $dirName = public_path("app/public");
    if (!is_dir($dirName)) throw new \Exception("Src dir [$dirName] not found");
    pkdebug("DirName: [$dirName]");
    $entries = scandir($dirName);
    $fileNames = [];
    foreach ($entries as $entry) {
      if (($entry === '.') || ($entry === '..')) continue;
      $fromPath = "$dirName/$entry";
      if (!file_exists($fromPath)) throw new \Exception("FromPath [$fromPath] not found");
      if (is_file($fromPath)) $fileNames[] = $entry;
    }
    return $fileNames;
  }









}
