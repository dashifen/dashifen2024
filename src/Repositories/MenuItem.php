<?php

namespace Dashifen\Dashifen2024\Repositories;

use Dashifen\Repository\Repository;
use Timber\MenuItem as TimberMenuItem;
use Dashifen\Repository\RepositoryException;

/**
 * MenuItem
 *
 * The MenuItem repository receives information from Timber menu items and
 * keeps only the information that we cram into our its properties.
 *
 * @property-read array  $classes
 * @property-read array  $children
 * @property-read bool   $current
 * @property-read string $label
 * @property-read string $url
 */
class MenuItem extends Repository
{
  protected array $classes = [];
  protected array $children = [];
  protected bool $current;
  protected string $label;
  protected string $url;
  
  public function __construct(TimberMenuItem $item)
  {
    parent::__construct([
      'classes'  => array_filter($item->classes),
      'children' => array_map(fn($child) => new MenuItem($child), $item->children),
      'current'  => $item->current || $item->current_item_ancestor || $item->current_item_parent,
      'label'    => $item->name(),
      'url'      => $item->url,
    ]);
  }
  
  /**
   * setClasses
   *
   * Sets the classes property.
   *
   * @param array $classes
   *
   * @return void
   */
  public function setClasses(array $classes): void
  {
    // the setCurrent method may lead us back here, and when it does, we don't
    // want to obliterate previously set classes.  instead, we merge the data
    // it sends us with what we received during instantiation.  then, we sort
    // everything just so it's easier to find in the DOM if we're inspecting
    // our results.
    
    $this->classes = array_merge($this->classes, $classes);
    sort($this->classes);
  }
  
  /**
   * setChildren
   *
   * Sets the children property.
   *
   * @param MenuItem[] $children
   *
   * @return void
   */
  public function setChildren(array $children): void
  {
    $this->children = $children;
  }
  
  /**
   * setCurrent
   *
   * Sets the current property.
   *
   * @param bool $current
   *
   * @return void
   */
  public function setCurrent(bool $current): void
  {
    $this->current = $current;
    
    // in addition to remembering our Boolean in case it's handy on the server
    // side, for the client side, we want to add a class to each menu item so
    // that they can get different styles as needed.
    
    $currentClass = $current ? 'is-current' : 'is-not-current';
    $this->setClasses([$currentClass]);
  }
  
  /**
   * setLabel
   *
   * Sets the label property.
   *
   * @param string $label
   *
   * @return void
   */
  public function setLabel(string $label): void
  {
    $this->label = $label;
  }
  
  /**
   * setUrl
   *
   * Sets the url property.
   *
   * @param string $url
   *
   * @return void
   * @throws RepositoryException
   */
  public function setUrl(string $url): void
  {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
      throw new RepositoryException('Invalid url: ' . $url,
        RepositoryException::INVALID_VALUE);
    }
    
    $this->url = $url;
  }
}
