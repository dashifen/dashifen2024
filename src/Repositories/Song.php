<?php

namespace Dashifen\Dashifen2024\Repositories;

use JsonException;
use Dashifen\Dashifen2024\Theme;
use Dashifen\Repository\Repository;

class Song extends Repository
{
  private const TRANSIENT = Theme::SLUG . '-recent-song';
  
  protected string $track;
  protected string $artist;
  protected string $image;
  protected bool $current;
  
  public function __construct()
  {
    //$songData = get_transient(self::TRANSIENT);
    //if ($songData === false && defined('LAST_FM_API_KEY')) {
      $songData = $this->getRecentSongData();
    //}
    
    parent::__construct($songData);
  }
  
  /**
   * getRecentSongData
   *
   * Hits the last.fm API to receive recent track information for Dash's
   * account.
   *
   * @return array
   *
   * @noinspection PhpUndefinedConstantInspection
   */
  protected function getRecentSongData(): array
  {
    $query = [
      'api_key' => LAST_FM_API_KEY,
      'method'  => 'user.getrecenttracks',
      'user'    => 'ddkees',
      'format'  => 'json',
    ];
    
    $url = 'http://ws.audioscrobbler.com/2.0/?' . http_build_query($query);
    $response = wp_remote_retrieve_body(wp_remote_get($url));
    
    try {
      
      // by passing the JSON_THROW_ON_ERROR flag to the following function,
      // we make it throw a JsonException instead of relying on the various
      // json error message functions.  this way we can just end up in the
      // catch block if we run into problems here.
      
      $json = json_decode(json: $response, flags: JSON_THROW_ON_ERROR);
      if (($track = ($json->recenttracks->track[0] ?? null)) === null) {
        
        // we end up in here if we couldn't access the first track listed in
        // my recent tracks from the API.  this would be weird because we
        // clearly received JSON that we could parse (or we'd already be in
        // the catch block), but it must not have contained information that
        // we could use.
        
        throw new JsonException();
      }
      
      $songData = [
        'track' => $track->name,
        
        // the JSON format is converted from an XML file.  that means we end
        // up with some oddly structured property names here.  luckily, we
        // can access the information in our object using some obscure and
        // infrequently needed PHP syntax:
        
        'artist'  => $track->artist->{"#text"},
        'image'   => $track->image[0]->{"#text"},
        'current' => $track->{"@attr"}->nowplaying === 'true',
      ];
    } catch (JsonException) {
      $songData = [
        'track'   => '',
        'artist'  => '',
        'image'   => '',
        'current' => false,
      ];
    }
    
    set_transient(self::TRANSIENT, $songData, 180);
    return $songData;
  }
  
  /**
   * setTrack
   *
   * Sets the track property.
   *
   * @param string $track
   *
   * @return void
   */
  protected function setTrack(string $track): void
  {
    $this->track = $track;
  }
  
  /**
   * setArtist
   *
   * Sets the artist property.
   *
   * @param string $artist
   *
   * @return void
   */
  protected function setArtist(string $artist): void
  {
    $this->artist = $artist;
  }
  
  /**
   * setImage
   *
   * Sets the image for this recent song to the parameter or a default if the
   * parameter is empty.
   *
   * @param string $image
   *
   * @return void
   */
  protected function setImage(string $image): void
  {
    $this->image = empty($image) ? 'compact-disc-solid.svg' : $image;
  }
  
  
  
  /**
   * setCurrent
   *
   * Sets the current property, i.e. is this track currently playing.
   *
   * @param bool $current
   *
   * @return void
   */
  protected function setCurrent(bool $current): void
  {
    $this->current = $current;
  }
}