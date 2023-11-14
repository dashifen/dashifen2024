<?php

namespace Dashifen\Dashifen2024\Repositories;

use stdClass;
use JsonException;
use Dashifen\Dashifen2024\Theme;
use Dashifen\Repository\Repository;

class Song extends Repository
{
  private const TRANSIENT = Theme::SLUG . '-recent-song';
  private const BLANK_SONG = [
    'track'   => '',
    'album'   => '',
    'artist'  => '',
    'image'   => '',
    'current' => false,
  ];
  
  protected string $track;    // the most recent track Dash listened to
  protected string $album;    // the album on which that track can be found
  protected string $artist;   // the artist which produced that album
  protected string $image;    // the album's cover art, when available
  protected bool $current;    // true if this song is currently playing
  private stdClass $song;     // the raw API data containing the above data
  
  public function __construct()
  {
    $songData = get_transient(self::TRANSIENT);
    if ($songData === false) {
      
      // if we couldn't get song data out of the database, then we can get it
      // from the API.  but, to do so, we need our API key.  it should be
      // defined as a constant in the wp-config file, but if it's not, then we
      // default to the blank song data defined above.
      
      $songData = defined('LAST_FM_API_KEY')
        ? $this->getRecentSongData()
        : self::BLANK_SONG;
    }
    
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
    $apiResponse = $this->getApiResponse();
    
    try {
      
      // by passing the JSON_THROW_ON_ERROR flag to the following function,
      // we make it throw a JsonException instead of relying on the various
      // json error message functions.  this way we can just end up in the
      // catch block if we run into problems here.
      
      $json = json_decode(json: $apiResponse, flags: JSON_THROW_ON_ERROR);
      if (($this->song = ($json->recenttracks->track[0] ?? null)) === null) {
        
        // we end up in here if we couldn't access the first track listed in
        // my recent tracks from the API.  this would be weird because we
        // clearly received JSON that we could parse (or we'd already be in
        // the catch block), but it must not have contained information that
        // we could use.  so, we throw our JsonException and end up in the
        // catch block anyway.
        
        throw new JsonException();
      }
      
      $songData = [
        'track' => $this->getSongDatum('name'),
        
        // the JSON format is converted from an XML file.  that means we end
        // up with some oddly structured property names here.  luckily, we
        // can access the information in our object using some obscure and
        // infrequently needed PHP syntax:
        
        'album'   => $this->getSongDatum('album'),
        'artist'  => $this->getSongDatum('artist'),
        'image'   => $this->getSongDatum('image'),
        'current' => $this->getSongDatum('nowplaying', false) === 'true',
      ];
    } catch (JsonException) {
      
      // if we couldn't read data from the API, then we'll just set up a
      // "blank" song.  the rest of the site will know that if we don't have
      // data simply not to show it.
      
      $songData = self::BLANK_SONG;
    }
    
    set_transient(self::TRANSIENT, $songData, 180);
    return $songData;
  }
  
  /**
   * getApiResponse
   *
   * Hits the Last.fm API and gets my most recent track listing there.
   *
   * @return string
   *
   * @noinspection PhpUndefinedConstantInspection
   */
  private function getApiResponse(): string
  {
    $query = [
      'api_key' => LAST_FM_API_KEY,
      'method'  => 'user.getrecenttracks',
      'user'    => 'ddkees',
      'format'  => 'json',
    ];
    
    $url = 'http://ws.audioscrobbler.com/2.0/?' . http_build_query($query);
    return wp_remote_retrieve_body(wp_remote_get($url));
  }
  
  /**
   * getSongDatum
   *
   * Gets a specific given datum from the song property that we set in the
   * getRecentSongData method above.
   *
   * @param string $property
   * @param mixed  $default
   *
   * @return mixed
   */
  private function getSongDatum(string $property, mixed $default = ''): mixed
  {
    return match ($property) {
      'name'                     => $this->song->name ?? '',
      
      // because the API JSON is converted from an XML file, we have some
      // strangely property names within it.  luckily, we can use the same
      // syntax that we use for variable property names to grab strangely
      // named properties, too.
      
      'track', 'artist', 'album' => $this->song->{$property}->{'#text'} ?? $default,
      'nowplaying'               => $this->song->{'@attr'}->nowplaying ?? $default,
      default                    => $default,
    };
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
   * setAlbum
   *
   * Sets the album property.
   *
   * @param string $album
   *
   * @return void
   */
  protected function setAlbum(string $album): void
  {
    $this->album = $album;
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