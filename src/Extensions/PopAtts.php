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
     'jsPopupTmpCallerDataAttr' => 'data-js-popup-template-calls',
     'jsPopupTmpCalledDataAttr' => 'data-js-popup-template-called',
     'encodedDataHolderClass'=>'encoded-data-holder',
     'encDatMdlDataAttr'=>'data-encoded-data-objtype',
     'encDatIdDataAttr'=>'data-encoded-data-objid',
     'encDatDatDataAttr'=>'data-encoded-data-data',
     'popTemplateClass'=>'pop-hidden-js-template',
     'popCallerClass'=>'pop-details-for-obj',
     'popAttrNameDataAttr'=>'data-enc-attr-name',
     'valueTemplateClass' => 'enc-attr-val-tpl',
     'valueHolderClass' => 'enc-attr-val-holder-tpl',
    ];

  public static function valueHolderClass() {
    return ' '.static::$attDefaults['valueHolderClass'].' ';
  }
  public static function valueTemplateClass() {
    return ' '.static::$attDefaults['valueTemplateClass'].' ';
  }
  public static function callerClass() {
    return ' '.static::$attDefaults['popCallerClass'].' ';
  }
  public static function dataAttrElement($attributeName=null) {
    if (!$attributeName) return static::$attDefaults['popAttrNameDataAttr'];
    if ($attributeName && is_string($attributeName) && strlen($attributeName)) {
      return  "class='".static::$attDefaults['valueHolderClass']."' ".
          static::$attDefaults['popAttrNameDataAttr']."='$attributeName' ";
    }
    throw new Exception ("Invalid AttributeName");
  }


  public static function templateClass() {
    return ' '.static::$attDefaults['popTemplateClass'].' ';
  }

  public static function jsInit() {
    $ps = new PartialSet();
    $ps[]="<script>\n";
    $ps[]="  var popDefObj = {\n";
    $defArr = [];
    foreach (static::$attDefaults as $defName => $defValue) {
      $defArr[] = "    $defName:'$defValue'";
    }
    $defStr = implode(",\n    ", $defArr);
    $ps[]="$defStr\n";
    $ps[]="  };\n";
    $ps[]="</script>\n";
    $ps[]="\n<style>\n";
    $ps[]="  .".static::templateClass()." {\n";
    $ps[]="    display:none;\n";
    $ps[]="  }\n";
    $ps[]="</style>\n";
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

  public static function holderDefault(PkModel $obj) {
    if (! $obj instanceOf PkModel) throw new Exception ("Has to be a PkModel");
    $id = $obj->id;
    $modelName = strtolower(getBaseName(get_class($obj)));
    return static::modelIdHolder($modelName,$id);
  }

  /** In the data element you want to call a detailed template. Just provide the
   * template name
   */
  public static function popCaller($templateName) {
    return " ".static::$attDefaults['jsPopupTmpCallerDataAttr']."='$templateName' ";
  }

  public static function templateDefiner($templateName, $title=null) {
    if ($title && is_string($title) && strlen($title)) {
      $title = " data-title='$title' ";
    } else {
      $title='';
    }
    return " ".static::$attDefaults['jsPopupTmpCalledDataAttr']."='$templateName' $title";
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
