<?php

namespace Dashifen\Dashifen2024\Repositories;

use stdClass;
use JsonException;
use Dashifen\Repository\Repository;

/**
 * @property-read int $sunrise
 * @property-read int $sunset
 * @property-read int $tomorrow
 */
class SolarTime extends Repository
{
  protected int $sunrise;
  protected int $sunset;
  protected int $tomorrow;
  private array $decodeCache = [];
  
  public function __construct(?array $today = null, ?array $tomorrow = null)
  {
    parent::__construct([
      'sunrise'  => $today !== null ? $this->extractSunrise($today) : '0',
      'sunset'   => $today !== null ? $this->extractSunset($today) : '0',
      'tomorrow' => $tomorrow !== null ? $this->extractSunrise($tomorrow) : '0',
    ]);
  }
  
  /**
   * getSunrise
   *
   * Decodes the specified HTTP response and returns the sunrise property of
   * it.
   *
   * @param array $day
   *
   * @return string
   */
  private function extractSunrise(array $day): string
  {
    return $this->getProperty($day, 'sunrise');
  }
  
  /**
   * getProperty
   *
   * Gets a property of our decoded HTTP response.
   *
   * @param array  $response
   * @param string $property
   *
   * @return string
   */
  private function getProperty(array $response, string $property): string
  {
    // md5 does collide, but the likelihood that it does so for us seems so
    // astronomically small that we're not going to worry about it.
    
    $hash = md5(serialize($response));
    $json = !isset($this->decodeCache[$hash])
      ? $this->decodeHttpResponse($response, $hash)
      : $this->decodeCache[$hash];
    
    return $json->results->{$property} ?? '0';
  }
  
  /**
   * decodeHttpResponse
   *
   * Given an HTTP response, decodes its JSON body into a stdClass, stores that
   * object in the decode cache property, and then returns it.
   *
   * @param array  $response
   * @param string $hash
   *
   * @return stdClass
   */
  private function decodeHttpResponse(array $response, string $hash): stdClass
  {
    try {
      $json = wp_remote_retrieve_body($response);
      $json = json_decode(json: $json, flags: JSON_THROW_ON_ERROR);
      
      // so we only have to decode information once, we store the json object
      // in our decode cache.  likely the work necessary isn't too great, but
      // there's no reason to do it if we don't have to.  then, since the
      // assignment operator returns the data assigned by side effect, we can
      // return the results of that caching assignment.
      
      return $this->decodeCache[$hash] = $json;
    } catch (JsonException) {
      
      // if we run into a JSON exception, we catch it here and return an empty
      // class.  this satisfies our type hint and allows our calling scope to
      // use null coalescing operators to set reasonable defaults for missing
      // properties.
      
      return new stdClass();
    }
  }
  
  /**
   * getSunset
   *
   * Decodes the specified HTTP response and returns the sunset property of it.
   *
   * @param array $day
   *
   * @return string
   */
  private function extractSunset(array $day): string
  {
    return $this->getProperty($day, 'sunset');
  }
  
  /**
   * setSunrise
   *
   * Sets the sunrise property.
   *
   * @param string $sunrise
   *
   * @return void
   */
  protected function setSunrise(string $sunrise): void
  {
    $this->sunrise = strtotime($sunrise);
  }
  
  /**
   * setSunset
   *
   * Sets the sunset property.
   *
   * @param string $sunset
   *
   * @return void
   */
  protected function setSunset(string $sunset): void
  {
    $this->sunset = strtotime($sunset);
  }
  
  /**
   * setTomorrow
   *
   * Sets the tomorrow property.
   *
   * @param string $tomorrow
   *
   * @return void
   */
  protected function setTomorrow(string $tomorrow): void
  {
    $this->tomorrow = strtotime($tomorrow);
  }
}