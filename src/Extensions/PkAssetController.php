<?php
namespace PkExtensions;
/**
 * PkAssetController - not super efficient, but mainly to ensure access to 
 * pklib.js, etc, which are required for some of the functionality of the 
 * PkExtensions package
 *
 * @author Paul Kirkaas
 */
class PkAssetController extends PkController {
  public function index() {
    return "Hello, return from asset index";

  }
}
