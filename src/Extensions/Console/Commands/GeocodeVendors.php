<?php
/* Paul Kirkaaas Feb 2019
 * Batch process to geocode (get lat/long from address)  vendors_new
 * Start with the low-hanging fruit - vendors rows with no lat, no long, no
 * geocoded status, but with at least city & state, zipcode & hopefully address
 */
namespace App\Console\Commands;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\VendorNew;
use App\City;
use App\State;
use App\WebsiteSettings;
use Carbon\Carbon;
use DB;

class GeocodeVendors extends Command {
  protected $signature = 'geocode:simple  {--batch=100}';
  protected $description = "Runs in small batches to geocode the easiest vendor records";
  public function __construct() {
    parent::__construct();
  }

  /*
  public static $sqlstr = "
SELECT VN.*, CT.city, ST.state_code FROM `vendors_new` AS VN
  JOIN `city` AS CT ON CT.id = VN.city_id
  JOIN `state` AS ST ON ST.id = CT.state_id
  WHERE VN.latitude = 0 AND VN.longitude = 0 AND VN.geocoded = 0 AND VN.zipcode IS NOT NULL
  LIMIT 100
    ";
   * *
   */
  public static $fieldtoprop = [ #Maps the table field name to the API property name
    'address'=>'address',
    'zipcode'=>'postal_code',
    'city'=>'locality',
    'state_code'=>'administrative_area',
      ];

  public static $sqlstr = "
SELECT VN.id, VN.zipcode, VN.address, CT.city, ST.state_code FROM `vendors_new` AS VN
  LEFT JOIN `city` AS CT ON CT.id = VN.city_id
  LEFT JOIN `state` AS ST ON ST.id = CT.state_id
  WHERE VN.latitude = 0 AND VN.longitude = 0 AND VN.geocoded = 0 AND VN.zipcode IS NOT NULL
  AND VN.zipcode != ''
  LIMIT 
    ";
//100
  /** Builds the query string for the Google Geo api
   * 
   * @param stdObj $row - row return from the query -
   *   keys: id, zipcode, address, city, state_code
   * @return querystring || false if not enough info to query
   * Sufficient conditions:
   * address OR
   * zipcode OR
   * city AND state_code
   */
  public static function buildGoogleQuery($row) {
    if (!$row->address && !$row->zipcode && 
         (!$row->city || !$row->state_code)){
      return false; #Not enough info to query
    }
    $componentStr = "components=country:US";
    if ($row->zipcode) {
      $componentStr .= "|postal_code:{$row->zipcode}";
    }
    if ($row->state_code) {
      $componentStr .= "|administrative_area:{$row->state_code}";
    }
    if ($row->city) {
      $componentStr .= ("|locality:".urlencode($row->state_code));
    }

    $addrStr = '';
    if ($row->address) {
      $addrStr = "address=".urlencode($row->address)."&";
    }
    $qstr=$addrStr.$componentStr;
    return $qstr;
  }

  public static $client; #Initialize at top of handle() method

  public static function vendorCSZ($vendor) {
    $id = $vendor->id;
    $sn = $vendor->state ? $vendor->state->state_code : " No State ";
    $cn = $vendor->city ? $vendor->city->city : " No City : vendor->city_id: ".$vendor->city_id." ";
    $zc = $vendor->zipcode ?? " No Zip ";
    return " ID: $id:  $sn | $cn | $zc ";
  }


  public static function processRow($row) {
    $url= "https://maps.googleapis.com/maps/api/geocode/json?";
    $key="&key=".WebsiteSettings::getSetting('google_map_api_key');
    $vendor = VendorNew::find($row->id);
    $vendor->geocodedate=Carbon::now();
    $vcsz = '';
    //  $vcsz = static::vendorCSZ($vendor);
    if(!$vendor->city || !$vendor->state || !$vendor->zipcode) {
      //echo "\nYes, vendor missing something\n";
      $vcsz = static::vendorCSZ($vendor);
    }
    //echo "\nEnter processRow - vendor csz\n$vcsz\n";
    $qstr = static::buildGoogleQuery($row);
    if (!$qstr) {
      $vendor->geocoded = VendorNew::$geostatus['INSUFFICIENT_DATA'];
      echo "\nInsufficient Data\n";
      $vendor->save();
      return;
    }
    $query = $url.$qstr.$key;
    $client = static::$client;
    //echo "\n\nThe Query:\n$query\n";
    $locraw = $client->get($query);
    $locres = $locraw->getBody();
    $loc = json_decode($locres);
    if (!is_object($loc)) {
      $vendor->geocoded = VendorNew::$geostatus['WR_UNKNOWN'];
      echo "\n\nWe don't know why: \n$query\nfailed\n\n";
      $vendor->save();
      return;
    }
    $status = $loc->status;
    if ($status !== "OK") {
      if (!empty(VendorNew::$geostatus[$status])) {
        $vendor->geocoded = VendorNew::$geostatus[$status];
      } else {
        $vendor->geocoded = VendorNew::$geostatus['WR_UNKNOWN'];
        echo "\n\nWe don't know why: \n$query\nfailed  with status: [$status]\n\n ";
      }
      $vendor->save();
      return;
    }  ## Hey, we might have lat/long!
    //$result = $loc->results[0]->geometry->location;
    $result = $loc->results[0];
    $coords = $result->geometry->location;
    $components = $result->address_components;
    if (!$coords->lat || !$coords->lng) {
      $vendor->geocoded = VendorNew::$geostatus['WR_UNKNOWN'];
      echo "\n\nWe don't know why: \n$query\nfailed  with OK status but no coords\n\n ";
      $vendor->save();
      return;
    }
    //echo  "\n The Geo address components:\n";
    ## Components is an array of sdObjs structured like:
    /**
     *     [0] => stdClass Object (
            [long_name] => 37214
            [short_name] => 37214
            [types] => [ [0] => postal_code]


    [1] => stdClass Object
        (
            [long_name] => Nashville
            [short_name] => Nashville
            [types] => Array
                (
                    [0] => locality
                    [1] => political
                )

        )

     */
    //print_r($components);
    if (!$vendor->zipcode) {
      $vendor->zipcode = static::extract('zipcode', $components);
    }
    if (!$vendor->city || !$vendor->state) { #Get them
      if (!$vendor->state) {
        $state_code = static::extract('state',$components);
        $vendor->state = State::where('state_code',$state_code)->first();
      }
      if (!$vendor->city) {
        $gcityarr = static::extract('city', $components,false); #Array of long & short
        //echo "\nCities return:\n";
        //print_r($gcityarr);
        if (is_array($gcityarr)) {
        foreach ($gcityarr as $city) {
          $citym = $vendor->state->cities()->where('city','LIKE',$city)->first();
          //print_r($citym);
          if (is_object($citym)) {
            //echo "\nCIty: [$city]; citym: ".$citym->city."\n";
            $vendor->city_id = $citym->id;
            break;
          }
        }
        }
      }
    }
    //$city = static::extract('city',$components);
    //$stateo = State::where('state_code',$state_code)->first();
    //$zip = static::extract('zipcode', $components);
    //echo "\nCity: [$city], State: [$state], ZIP: [$zip]\n\n";
    //echo "\nUpdating a Vendor\n";
    //if (!$vendor->city) {
     // $vcity = 
    //}
    $vendor->geocoded =  VendorNew::$geostatus['OK'];
    $vendor->latitude = $coords->lat;
    $vendor->longitude = $coords->lng;
    $vendor->save();
    $vendor = $vendor->fresh();
    /*
    if ($vcsz) {
      $post = static::vendorCSZ($vendor);
      echo "\n\nBefore Gecode: \n$vcsz\nAfter:\n$post\n\n";
    }
     * 
     */
    return;
    /* Location stdClass Object:
    [lat] => 39.2844743
    [lng] => -76.5915028
     */
  }


  /**
    A State is:  administrative_area_level_1
   *A city is 'locality'
   * zipcode - postal_code
   */


  /**
   * 
   * @param string $field - city, state, or zip
   * @param array $components -- array of address component objects
   * @param boolean $short - return the short name (default)
   *     else array of both
   */
  public static function extract($field,$components,$short=true) {
    $ftog = [
        'state'=>'administrative_area_level_1',
        'city'=>'locality',
        'zipcode'=>'postal_code',
    ];
    $match = $ftog[$field];
    foreach ($components as $component) {
      #Each component object has an array of type names
      foreach($component->types as $type) {
        if ($type === $match) { #This component matches
          if ($short) {
            return $component->short_name;
          } else {

            return [ $component->short_name,$component->long_name];
          }
        }
      }
    }
  }


  public function handle() {
    static::$client = new Client();
    $batch = $this->option('batch');
    echo "\nRunning a batch of $batch\n";


    $result = DB::select(DB::raw(static::$sqlstr.$batch));
    #Output:
    /* [id] => 4473
       [zipcode] => 29624
       [address] => 131 Video Warehouse Way
       [city] => Anderson
       [state_code] => IN
     */

    //echo ("The results:\n\n");
    echo "\n\nGeocoding Vendors\n";
    foreach ($result as $idx => $row) {
      echo "$idx, ";
      static::processRow($row);
    }
  }

/*
SELECT VN.*, CT.city, ST.state_code FROM `vendors_new` AS VN
  JOIN `city` AS CT ON CT.id = VN.city_id
  JOIN `state` AS ST ON ST.id = CT.state_id
  WHERE VN.latitude = 0 AND VN.longitude = 0 AND VN.geocoded = 0 AND VN.zipcode IS NOT NULL
  LIMIT 100
 * *
 */




  //put your code here
}
