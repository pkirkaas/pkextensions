<?php
namespace PkExtensions;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;

/**
 * Guesses the file extension corresponding to a given mime type.
 * Description of PkMimetypeGuesser - The Symfony mime type guesser reports
 * mimetype 'application/octet-stream when the uploaded file exceeds the allowed
 * limit. Not very informative.

 * PHP seems to work fine - so plug this in
 * @author pkirk
 */
 
class PkMimeTypeGuesser implements MimeTypeGuesserInterface {
  public function guess($path) {
    pkdebug("The path is: [$path]");
    return mime_content_type($path);
  }
  public function __construct() {
    MimeTypeGuesser::getInstance()->register($this);
  }
}
