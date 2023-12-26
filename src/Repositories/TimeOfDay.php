<?php

namespace Dashifen\Dashifen2024\Repositories;

use DateTime;
use Exception;
use DateTimeZone;
use Dashifen\Dashifen2024\Theme;
use Dashifen\Repository\Repository;
use Dashifen\WPDebugging\WPDebuggingTrait;
use Dashifen\Repository\RepositoryException;

class TimeOfDay extends Repository
{
  use WPDebuggingTrait;
  
  protected int $sunrise;
  protected int $sunset;
  protected int $tomorrow;
  protected int $timeOfDayNumber;
  protected string $timeOfDay;
  
  /**
   * __construct
   *
   * Uses the database or the sunrise-sunset.org API to get sunrise and sunset
   * times and calculates the site's time of day based on that.
   *
   * @throws Exception
   */
  public function __construct(
    private readonly string $format = 'n/j/Y \a\\t g:ia'
  ) {
    parent::__construct($this->getSolarTime());
  }
  
  /**
   * getSolarTime
   *
   * Returns the array of data we need to identify the relative solar time of
   * day in Dash's rough location.
   *
   * @param bool $forceFetch
   *
   * @return array
   * @throws Exception
   * @throws RepositoryException
   */
  private function getSolarTime(bool $forceFetch = false): array
  {
    $now = $this->getCurrentTimestamp();
    $solarTime = $this->getApiData($forceFetch);
    $numericTimeOfDay = $this->isNight($now, $solarTime->sunset)
      
      // if it's nighttime, we add 100 to our numeric time of day.  this is
      // explained further below in the match statement.
      
      ? $this->calculatePercent($now, $solarTime->sunset, $solarTime->tomorrow) + 100
      : $this->calculatePercent($now, $solarTime->sunrise, $solarTime->sunset);
    
    return $numericTimeOfDay > 200
      ? $this->getSolarTime(true)
      : array_merge($solarTime->toArray(), [
        'timeOfDayNumber' => $numericTimeOfDay,
        'timeOfDay'       => match (true) {
          $numericTimeOfDay <= 25  => 'morning',
          $numericTimeOfDay <= 75  => 'day',
          $numericTimeOfDay <= 100 => 'evening',
          
          // here's where our +100 during the nighttime comes in.  we still
          // calculate the percentage between sunset and tomorrow's sunrise, but
          // by adding 100 to it, we get numbers between 100 and 200 during the
          // night and between 0 and 100 during the day.
          
          $numericTimeOfDay <= 125 => 'twilight',
          $numericTimeOfDay <= 175 => 'night',
          $numericTimeOfDay <= 200 => 'dawn',
        },
      ]);
  }
  
  /**
   * getApiData
   *
   * When necessary, accesses the sunrise/sunset API or grabs existing
   * information out of the database.
   *
   * @param bool $forceFetch
   *
   * @return SolarTime
   * @throws RepositoryException
   */
  private function getApiData(bool $forceFetch): SolarTime
  {
    $transient = Theme::SLUG . '-solar-time';
    $solarTime = get_transient($transient);
    
    if ($forceFetch || $solarTime === false) {
      $today = $this->getApiResponse('today');
      $tomorrow = $this->getApiResponse('tomorrow');
      $solarTime = $this->isValidResponse($today) && $this->isValidResponse($tomorrow)
        ? new SolarTime($today, $tomorrow)
        : new SolarTime();
      
      set_transient($transient, $solarTime, DAY_IN_SECONDS);
    }
    
    return $solarTime;
  }
  
  /**
   * getApiResponse
   *
   * Given a day (i.e., today or tomorrow) gets the API response from the
   * sunrise-sunset.org API.
   *
   * @param string $day
   *
   * @return array
   */
  private function getApiResponse(string $day): array
  {
    $query = [
      'lat'       => 38.8051095,
      'lng'       => -77.0470229,
      'formatted' => 0,
      'date'      => $day,
    ];
    
    $url = 'https://api.sunrise-sunset.org/json?' . http_build_query($query);
    $response = wp_remote_get($url);
    
    // in the unlikely event that we generate a WP_Error with the remote get
    // call, we'll return an empty array.  otherwise, we just return what ever
    // the remote get returned.
    
    return !is_a($response, 'WP_Error') ? $response : [];
  }
  
  /**
   * isValidResponse
   *
   * Returns true if we get a 200 status code within our API response.
   *
   * @param array $response
   *
   * @return bool
   */
  private function isValidResponse(array $response): bool
  {
    return wp_remote_retrieve_response_code($response) === 200;
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
    
    return $this->getTimestamp('UTC')->getTimestamp();
  }
  
  /**
   * getTimestamp
   *
   * Given a timezone and (optionally) a timestamp, returns a timestamp in the
   * specified timezone.
   *
   * @param string   $timezone
   * @param int|null $timestamp
   *
   * @return DateTime
   * @throws Exception
   */
  private function getTimestamp(string $timezone, ?int $timestamp = null): DateTime
  {
    $timezone = new DateTimeZone($timezone);
    $datetime = new DateTime(timezone: $timezone);
    if ($timestamp !== null) {
      
      // if the timestamp isn't null, then we're taking an existing time and
      // converting it into the specified timestamp.  we set our DateTime
      // object's internal time to the one we're given to make that happen.
      
      $datetime->setTimestamp($timestamp);
    }
    
    return $datetime;
  }
  
  /**
   * isNight
   *
   * This straightforward method exists to make code above a little more
   * readable.  It returns true if the current time is after sunset, i.e. if it
   * is night.
   *
   * @param int $now
   * @param int $sunset
   *
   * @return bool
   */
  private function isNight(int $now, int $sunset): bool
  {
    return $now > $sunset;
  }
  
  /**
   * calculatePercent
   *
   * Given a timestamp, now, between sunrise and sunset, returns the percent
   * of the day that has elapsed.
   *
   * @param int $now
   * @param int $start
   * @param int $end
   *
   * @return int
   */
  private function calculatePercent(int $now, int $start, int $end): int
  {
    // to find the amount of time elapsed after $start as represented by $now,
    // we need both the number of seconds between $now and $start and the total
    // number of seconds between $end and $start.  once we have that, it's a
    // simple division and multiplication operation to produce a percentage.
    
    return round(($now - $start) / ($end - $start) * 100);
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
    return $this->getLocalTime($this->sunrise)->format($this->format);
  }
  
  /**
   * getLocalTimestamp
   *
   * Given a timestamp in UTC, convert it to Eastern US time.
   *
   * @param int $timestamp
   *
   * @return DateTime
   * @throws Exception
   */
  private function getLocalTime(int $timestamp): DateTime
  {
    return $this->getTimestamp('America/New_York', $timestamp);
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
    return $this->getLocalTime($this->sunset)->format($this->format);
  }
  
  /**
   * setTomorrow
   *
   * Sets the tomorrow property.
   *
   * @param int $tomorrow
   *
   * @return void
   */
  protected function setTomorrow(int $tomorrow): void
  {
    $this->tomorrow = $tomorrow;
  }
  
  /**
   * getTomorrow
   *
   * Returns tomorrow's sunrise in New York's timezone.
   *
   * @return string
   * @throws Exception
   */
  protected function getTomorrow(): string
  {
    return $this->getLocalTime($this->tomorrow)->format($this->format);
  }
  
  /**
   * setTimeOfDayNumber
   *
   * Sets the timeOfDayNumber property.
   *
   * @param int $timeOfDayNumber
   *
   * @return void
   * @throws RepositoryException
   */
  protected function setTimeOfDayNumber(int $timeOfDayNumber): void
  {
    if ($timeOfDayNumber < 0 || $timeOfDayNumber > 200) {
      throw new RepositoryException(
        'Invalid numeric time of day: ' . $timeOfDayNumber,
        RepositoryException::INVALID_VALUE
      );
    }
    
    $this->timeOfDayNumber = $timeOfDayNumber;
  }
  
  /**
   * setTimeOfDay
   *
   * Sets the time of day property.
   *
   * @param string $timeOfDay
   *
   * @return void
   * @throws RepositoryException
   */
  protected function setTimeOfDay(string $timeOfDay): void
  {
    $timesOfDay = ['morning', 'day', 'evening', 'twilight', 'night', 'dawn'];
    
    if (!in_array($timeOfDay, $timesOfDay)) {
      throw new RepositoryException(
        'Invalid current time of day: ' . $timeOfDay,
        RepositoryException::INVALID_VALUE
      );
    }
    
    $this->timeOfDay = $timeOfDay;
  }
}