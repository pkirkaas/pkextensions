<?php
/** This class supports getting the pop-up attributes named correctly and consistently
 * throughout the applicaton, including in the JavaScript
 *
 * Usage: Say you want to encode details for multiple 'Borrower' instances returned
 * from a search, present the results in rows, but pop up additional details when the
 * user hovers over an item.
 * First, to ensure the JS uses the same terms/definitions as the PHP, in the
 * top/head of your app template, echo PopAtts::jsInit(); {!!PopAtts::jsInit()!!}
 *
 * This will create a global JS object
   var PopAttDefs={
     modelAndIdHolder:static::$modelAndIdHolder,
     ....
   }
 */

namespace PkExtensions;
use PkExtensions\Models\PkModel;
use Exception;
class PopAtts {

  public static $attDefaults = [
     #CSS class to add to the element that will trigger the popup
    //'detPopper' =>  "pk-details-popup-activator",
       #CSS class to add to the element that will contain the model
       #type and ID to provide details for
     //'modelAndIdHolder' => ' pk-model-id-holder',

         #data attribute holding the model type name
     'dataModelType' =>"data-model-type-name",

         #data attribute holding the model ID
     'dataModelId'=>"data-model-id",
     'jsPopupTmpCallerDataAttr' => 'data-js_popup-template-calls',
     'jsPopupTmpCalledDataAttr' => 'data-js-popup-template-called',
     'encodedDataHolderClass'=>'encoded-data-holder',
     'encDatMdlDataAttr'=>'data-encoded-data-objtype',
     'encDatIdDataAttr'=>'data-encoded-data-objid',
     'encDatDatDataAttr'=>'data-encoded-data-data',

    ];

  public static function jsInit() {
    $ps = new PartialSet();
    $ps[]="<script>\n";
    $ps[]="  var popDefObj = {\n";
    $defArr = [];
    foreach (static::$attDefaults as $defName => $defValue) {
      $defArr[] = "    $defName:'$defValue'";
    }
    $defStr = implode(",\n", $defArr);
    $ps[]="$defStr\n";
    $ps[]="  };\n";
    return $ps;
  }

  /** Used your results wrapping row - 
   *
   * @param string $modelname: The name of the model type: 'borrower', say.
   * @param string|int $modelid: The id of the particular model result.
   * @return string - the data-xxx definitions for your row, 
   *   or whatever your containing element is.
   * Example Usage: <div {{PopAtts::modelIdHolder($modelName, $id)}}> ....
   */
  public static function modelIdHolder($modelname, $modelid) {
    $dataName = static::$attDefaults['dataModelType']."='$modelname' ";
    $dataId = static::$attDefaults['dataModelId']."='$modelid' ";
    return " $dataName $dataId ";
  }

  /** In the data element you want to call a detailed template. Just provide the
   * template name
   */
  public static function popCaller($templateName) {
    return " ".static::$jsPopupTmpDataAttr."='$templateName' ";
  }

  public static function templateDefiner($templateName) {
  }


/** Creates an HTML element or array of elements holding encoded PkModel attribute
 * data for use by JS on the page.
 * @param PkModel|arrayish PkModels - $pkmodels - the model(s) to get attributes
 * for. Can be mixed types - but then have to use the default model typename
 * @param string|null $typename - what to call the type in the element.
 *   Default is basename of the PkModel class.
 * @return HTML String|Array HTML Strings - elements in the form:
 *  <div class='encoded-data-holder' data-encoded-data-objtype="$typename"
 *   data-encoded-data-objid="$id" data-encoded-data-data="$encoded_data"></div>"
 */
  public static function jsObjDataGen($pkmodels, $typename = null) {
    if ($pkmodels instanceOf PkModel) {
      $pkmodels = [$pkmodels];
    }
    $encodedDataHolderClass = static::$attDefaults['encodedDataHolderClass'];
    $encDatMdlDataAttr=static::$attDefaults['encDatMdlDataAttr'];
    $encDatIdDataAttr=static::$attDefaults['encDatIdDataAttr'];
    $encDatDatDataAttr=static::$attDefaults['encDatDatDataAttr'];
    //if (!$typename) $typename = strtolower(getBaseName($typeof));
    $ps = new PartialSet();
    foreach ($pkmodels as $instance) {
      if (!$instance instanceOf PkModel) throw new Exception ("Wrong type");
      if (!$typename) $thistypename = strtolower(getBaseName(get_class($instance)));
      else $thistypename = $typename;
      $encdata = $instance->getEncodedCustomAttributes();
      $id = $instance->id;
      $ps[] = "<div class='$encodedDataHolderClass' $encDatMdlDataAttr='$thistypename'
         $encDatIdDataAttr='$id' $encDatDatDataAttr='$encdata'></div>\n";
    }
    return $ps;

  } 


}
