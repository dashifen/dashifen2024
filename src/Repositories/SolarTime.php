<?php

namespace Dashifen\Dashifen2024\Repositories;

use DateTime;
use Exception;
use DateTimeZone;
use DateTimeImmutable;
use DateTimeInterface;
use Dashifen\Dashifen2024\Theme;
use Dashifen\Repository\Repository;
use Dashifen\WPDebugging\WPDebuggingTrait;
use Dashifen\Repository\RepositoryException;

class SolarTime extends Repository
{
  use WPDebuggingTrait;
  
  private const TRANSIENT = Theme::SLUG . '-solar-time';
  
  protected int $sunrise;
  protected int $sunset;
  protected int $percent;
  protected string $currently;
  private string $format = 'n/j/Y \a\\t h:ia';
  
  /**
   * __construct
   *
   * Uses the database or the sunrise-sunset.org API to get sunrise and sunset
   * times and calculates the site's time of day based on that.
   *
   * @throws Exception
   */
  public function __construct()
  {
    $solarTime = $this->getSolarTime();
    $sunrise = strtotime($solarTime['sunrise']);
    $sunset = strtotime($solarTime['sunset']);
    $now = $this->getCurrentTimestamp();
    
    // if the current time is between sunrise and sunset, we use a method below
    // to calculate the percentage of time that has elapsed between the two of
    // them.  then, we can pass information that we've gathered to our parent's
    // constructor along with a final specification of the rough time of day
    // that it currently is.
    
    $percent = $now > $sunrise && $now < $sunset
      ? $this->calculatePercent($now, $sunrise, $sunset)
      : 0;
    
    parent::__construct([
      'sunrise'   => $sunrise,
      'sunset'    => $sunset,
      'percent'   => $percent,
      'currently' => match (true) {
        $percent >= 25 && $percent < 75 => 'day',
        $percent === 0                  => 'night',
        default                         => 'twilight',
      },
    ]);
  }
  
  /**
   * getSolarTime
   *
   * When necessary, accesses the sunrise/sunset API or grabs existing
   * information out of the database.
   *
   * @return array
   */
  private function getSolarTime(): array
  {
    $solarTime = get_transient(self::TRANSIENT);
    
    // if the transient doesn't exist, then get_transient returns false.  in
    // that case, we'll hit the API and get the information from them.  then,
    // we set our transient so that, in the off chance that someone visits the
    // site more than once per day, we don't bother the API too much.
    
    if (!$solarTime) {
      $response = wp_remote_get('https://api.sunrise-sunset.org/json?lat=38.8051095&lng=-77.0470229&date=today&formatted=0');
      if (wp_remote_retrieve_response_code($response) === 200) {
        $response = json_decode(wp_remote_retrieve_body($response));
        
        $solarTime = [
          'sunrise' => $response->results->sunrise,
          'sunset'  => $response->results->sunset,
        ];
        
        // we store the information we just grabbed from the API in a transient
        // that we indicate should last for one day (in seconds).  that way, if
        // someone comes back today to look at the site again, we don't have to
        // get information from the API that won't have changed yet.
        
        set_transient(self::TRANSIENT, $solarTime, 24 * 60 * 60);
      }
    }
    
    return $solarTime;
  }
  
  /**
   * getCurrentTimestamp
   *
   * Returns the current timestamp in UTC.
   *
   * @return int
   * @throws Exception
   */
  private function getCurrentTimestamp(): int
  {
    // since the DateTimeImmutable object's default first argument value of
    // "now" is exactly what we want it to be, we can use a named argument to
    // send in only the timezone that we want it to use for this calculation.
    // because the API works only in UTC, that's what we have to work in here,
    // too.
    
    return (new DateTimeImmutable(
      timezone: new DateTimeZone('UTC')
    ))->getTimestamp();
  }
  
  /**
   * calculatePercent
   *
   * Given a timestamp, now, between sunrise and sunset, returns the percent
   * of the day that has elapsed.
   *
   * @param int $now
   * @param int $sunrise
   * @param int $sunset
   *
   * @return int
   */
  private function calculatePercent(int $now, int $sunrise, int $sunset): int
  {
    // the percent of today that has elapsed is the ratio of the number of
    // seconds since sunrise divided by the number of seconds between sunrise
    // and sunset.  to make it a percent, we then multiply by 100 and round to
    // the nearest integer.
    
    $elapsed = $now - $sunrise;
    $lengthOfDay = $sunset - $sunrise;
    return round($elapsed / $lengthOfDay * 100);
  }
  
  /**
   * setSunrise
   *
   * Sets the sunrise property.
   *
   * @param int $sunrise
   *
   * @return void
   */
  protected function setSunrise(int $sunrise): void
  {
    $this->sunrise = $sunrise;
  }
  
  /**
   * getSunrise
   *
   * Returns sunrise in New York's timezone.
   *
   * @return string
   * @throws Exception
   */
  protected function getSunrise(): string
  {
    return $this->getTimezoneTimestamp($this->sunrise)->format($this->format);
  }
  
  /**
   * getTimezoneTimestamp
   *
   * Given a timestamp in UTC, convert it to Eastern US time.
   *
   * @param int $utcTimestamp
   *
   * @return DateTimeInterface
   * @throws Exception
   */
  private function getTimezoneTimestamp(int $utcTimestamp): DateTimeInterface
  {
    // just like the method above to get the current UTC timestamp, this one
    // can leave the first argument to our DateTime constructor alone and
    // specify US Eastern time as a timezone with a named argument.  this one
    // can't be an immutable object, though; instead we use a regular DateTime
    // and then specify a different timestamp using our parameter.
    
    $datetime = new DateTime(timezone: new DateTimeZone('America/New_York'));
    $datetime->setTimestamp($utcTimestamp);
    return $datetime;
  }
  
  /**
   * setSunset
   *
   * Sets the sunset property.
   *
   * @param int $sunset
   *
   * @return void
   */
  protected function setSunset(int $sunset): void
  {
    $this->sunset = $sunset;
  }
  
  /**
   * getSunset
   *
   * Returns sunset in New York's timezone.
   *
   * @return string
   * @throws Exception
   */
  protected function getSunset(): string
  {
    return $this->getTimezoneTimestamp($this->sunset)->format($this->format);
  }
  
  /**
   * setPercent
   *
   * Sets the percent property.
   *
   * @param int $percent
   *
   * @return void
   * @throws RepositoryException
   */
  protected function setPercent(int $percent): void
  {
    if ($percent < 0 || $percent > 100) {
      throw new RepositoryException(
        'Invalid percent: ' . $percent,
        RepositoryException::INVALID_VALUE
      );
    }
    
    $this->percent = $percent;
  }
  
  /**
   * setCurrently
   *
   * Sets the current rough time of day.
   *
   * @param string $currently
   *
   * @return void
   * @throws RepositoryException
   */
  protected function setCurrently(string $currently): void
  {
    if (!in_array($currently, ['night', 'twilight', 'day'])) {
      throw new RepositoryException(
        'Invalid current time of day: ' . $currently,
        RepositoryException::INVALID_VALUE
      );
    }
    
    $this->currently = $currently;
  }
  
  /**
   * setFormat
   *
   * Sets the format in which we return sunrise and sunset information.  It is
   * assumed that this matches the format for the date function and weird stuff
   * probably happens if it isn't.
   *
   * @param string $format
   *
   * @return void
   */
  public function setFormat(string $format): void
  {
    $this->format = $format;
  }
}