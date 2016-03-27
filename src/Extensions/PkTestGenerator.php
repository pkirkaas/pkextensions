<?php
 /** Generates test data - 
  */

namespace PkExtensions;

class PkTestGenerator {
  /**
   * Returns random data item (possibly with invalid data item) from an array 
   * @param array $dataArr
   * @param type $badData
   * @return type
   */
  public static function getRandomData(Array $dataArr) {
    return $dataArr[mt_rand(0, sizeof($dataArr) - 1)];
  }



  /**
   * Returns a date within the the range (default: years)
   * @param integer $yrsFrom: Plus or Minus years from now
   * @param integer $yrsTo: Plus or Minus years from now. Default: Now
   * @param integer $multiplier - num days - default, 365, 1 yr
   * @return string: Pretty Date within the range
   */
  public static function getRandomUnixDateFromRange($from, $to=0, $multiplier = 365) {
    $from = $from * $multiplier;
    $to = $to * $multiplier;
    if ($from < $to) {
      $rndOffset = mt_rand($from, $to);
    } else {
      $rndOffset = mt_rand($to, $from);
    }
    $day = 24 * 60 * 60;
    $now = time();
    $rndTime = $now + ($rndOffset * $day);
    return $rndTime;
  }



  /**
   * 
   * @param int $from - num of units from now
   * @param int $to - num of units from now
   * @param int $multiplier - multiply from/to days - default, 365, so from/to are years
   * @return type
   */
  public static function getRandomSqlDateFromRange($from, $to=0, $multiplier = 365) {
    return static::sqlDateFromUnix(static::getRandomUnixDateFromRange($from, $to, $multiplier));
  }

  public static function getRandomSqlRecentDate($multiplier = 365) {
    return static::sqlDateFromUnix(static::getRandomUnixDateFromRange(0, -1, $multiplier));
  }

  public static function getRandomSqlBirthdate($multiplier = 365) {
    return static::sqlDateFromUnix(static::getRandomUnixDateFromRange(-20, -70, $multiplier));
  }

  public static function sqlDateFromUnix($unixDate) {
    //$mysqldate = date('Y-m-d H:i:s', $unixDate);
    $mysqldate = date('Y-m-d', $unixDate);
    return $mysqldate;
  }


######################  THIS DOESN'T WORK YET W. LARAVEL - BUT GET IT GOING.....
  /** 'Uploads' a random file from the directory, of 'type'
   * 
   * @param string $path - directory path
   * @param string $type - file object 'type' - 'image', 'audio', 'doc', etc.
   *     default: 'image'
   * @return int - the ID of the newly uploaded file object
   */
   /*
  public static function importRandomFileFromDir($dir, $type = 'image') {
    if (!is_dir($dir)) {
      pkdebug("Couldn't resolve [$dir] to a directory");
      return null;
    }
    $entries = scandir($dir);
    if (!is_array($entries) || !sizeOf($entries)) {
      pkdebug("No entries in [$dir]. Entries:", $entries);
      return null;
    }
    #Make array of valid file type paths from entries
    $paths = [];
    foreach ($entries as $entry) {
      $newpath = pkuntrailingslashit($dir) . "/$entry";
      if (!is_file($newpath)) continue;
      $valid = BaseFileHandler::validateFiletype($newpath, $type);
      if ($valid === false) continue;
      $paths[] = $newpath;
    }
    //pkdebug("For [$dir], got valid paths:", $paths);
    #We have a set of valid file paths of the required type. Pick one:
    $path = static::getRandomData($paths);
    $relPath = BaseFileHandler::relPathFromFullPath($path);
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
  }
  */












}
