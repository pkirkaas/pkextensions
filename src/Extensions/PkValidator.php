<?php namespace PkExtensions;
use Illuminate\Validation\Validator;
use Symfony\Component\Translation\TranslatorInterface;
/** Custom Validator for Laravel */
class PkValidator extends Validator {
    /**
     * Create a new Validator instance.
     *
     * @param  \Symfony\Component\Translation\TranslatorInterface  $translator
     * @param  array  $data
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return void
     */
    public function __construct(TranslatorInterface $translator, array $data, array $rules, array $messages = [], array $customAttributes = []) {
      pkdebug("Making PkValidator: Translator:", $translator, "Data:", $data, "Rules:", $rules, "Messages:", $messages, "CustomAtts:", $customAttributes);
       parent::__construct($translator, $data, $rules, $messages, $customAttributes);
       //$this->setCustomMessages(['id.test'=>"Failed ID Test!", 'test'=>'Failed the Test']);
    }
    /*
  public function validateTest ($attribute, $value, $params = null) {
    return true;
  }
     */
  public function validateTest2 ($attribute, $value, $params = null) {
    \pkdebug ("In ValidateTest: attriubute: [$attribute], value: [$value]; Params:", $params);
    $errorMsg = "No, Not, not Valid!";
    $this -> setCustomMessages(['test2'=>"Quite a problem"]);
    
    return false;
  }

    /** 
     * The uniquecombo validator expects $field_name to be 'id', $value to be the
     * value of the existing ID in the table if an update, or empty if a new entry.
     * $paramaters are an indexed array, first the table name, then the field names
     * to compare for uniqueness.
     */
  public function uniquecombo($field_name, $value, $parameters = null) {
     if (!is_array($parameters) || !sizeOf($parameters)) throw new \Exception("No Parameters passed to UniqueCombo!");
      $table_name = array_shift($parameters);
      $field_names = $parameters;
      $data = $this->getData();
      #Get the subset of $data with keys in $field_names:
      $compareFields = array_intersect_key($data, array_flip($field_names));
      //pkdebug("CompareFields:", $compareFields);
      $builder=DB::table($table_name);
      if (Schema::hasColumn($table_name, 'deleted_at')) { #Remove from query
        $builder = $builder->whereNull('deleted_at');
      }
      foreach ($compareFields as $fname => $fval) {
        $builder = $builder->where($fname,'=',$fval);
      }
      $arrOfObjs = $builder->get();
      $arrOfArrs = json_decode(json_encode($arrOfObjs), true);
      if (!$arrOfArrs || !sizeOf($arrOfArrs)) return true;
      if (sizeOf($arrOfArrs) > 1) {
        $this -> setCustomMessages(['uniquecombo'=>"Failed Unique Combo step 1"]);
        return false;
      }
      $row = $arrOfArrs[0];
      if ($row[$field_name] != $value) {
        $this -> setCustomMessages(['uniquecombo'=>"Failed Unique Combo step 2"]);
        return false;
      }



      
      //pkdebug("FieldName: [$field_name], value: [$value], ROW:", $row);
      return true;

  }
    /*
     * 
     */
}
