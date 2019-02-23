<?php
/** (c) 2019 Paul Kirkaas 
 * Get the approximate user location from the request.  If testing on localhost,
 * makes external call to get localhost / server IP
 */
namespace PkExtensions\Traits;
use GuzzleHttp\Client;

trait GetUserLocationTrait {
  /**
   * The URL that takes an IP address & returns a location structure
   * @var URL 
   */
  public static $ipapiurl = "http://ip-api.com/json";
  /** For testing, if your user IP is "127.0.0.1", get your server's IP 
   * from an external request.
   * @var URL
   */
  public static $whatsmyipurl = "http://ipv4bot.whatismyipaddress.com";
  /**
   * Returns the location as an array for the given IP address, if no
   * IP address, the IP from the user/request
   * @param IP|null $ip
   * @return stdObj location:
   *   stdClass:{
  ["as"]=> "AS20001 Time Warner Cable Internet LLC"
  ["city"]=> "Marina del Rey"
  ["country"]=> "United States"
  ["countryCode"]=> "US"
  ["isp"]=> "Spectrum"
  ["lat"]=> float(33.9779)
  ["lon"]=> float(-118.4525)
  ["org"]=> "Charter Communications"
  ["query"]=> "172.116.160.203"
  ["region"]=> "CA"
  ["regionName"]=> "California"
  ["status"]=> "success"
  ["timezone"]=> "America/Los_Angeles"
  ["zip"]=> "90292"
}
   */
  public static function getIpLocation($ip=null) {
    if (!$ip) {
      $ip=request()->ip();
    }
    $client = new Client();
    if ($ip === "127.0.0.1") { #Request from server
      $ipresp = $client->get(static::$whatsmyipurl);
      $ipbody = $ipresp->getBody();
      $ip = $ipbody;
    }
      
    $response = $client->get(static::$ipapiurl."/$ip");
    $jsonlocation = $response->getBody();
    $location = json_decode($jsonlocation);
    return $location;
  }
}
