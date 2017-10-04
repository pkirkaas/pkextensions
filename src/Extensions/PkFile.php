<?php
/*
 * Extends Laravel "File" to inlcude features of UploadedFile, like, "isValid"
 */

namespace PkExtensions;
use Illuminate\Http\File;

/**
 * Description of PkFile
 *
 * @author pkirkaas
 */
class PkFile extends File {
  #Just checks if the file exists
  public function isValid() {
    return file_exists($this->getPathname());
  }
}
