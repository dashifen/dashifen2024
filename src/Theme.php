<?php

namespace Dashifen\Dashifen2024;

use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;
use Dashifen\WPHandler\Handlers\HandlerException;
use Dashifen\WPHandler\Handlers\Themes\AbstractThemeHandler;

class Theme extends AbstractThemeHandler
{
  public const SLUG = 'dashifen2024';
  
  private bool $coreTemplateLoaderPrevention;
  
  /**
   * initialize
   *
   * Uses addAction and/or addFilter to attach protected methods of this object
   * to the ecosystem of WordPress action and filter hooks.
   *
   * @return void
   * @throws HandlerException
   */
  public function initialize(): void
  {
    if (!$this->isInitialized()) {
      $this->addAction('init', 'initializeAgents', 1);
      $this->addFilter('timber/loader/loader', 'addTimberNamespaces');
      $this->addAction('after_setup_theme', 'prepareTheme');
      $this->addFilter('wp_using_themes', 'preventCoreTemplateLoader');
      
      // we use the PHP_INT_MAX as the priority level for this action which all
      // but guarantees that we'll be running it last.  this allows us to use
      // the template redirect action for it's more typical purpose, i.e. a
      // header redirect, if we need to.
      
      $this->addAction('template_redirect', 'almostAlwaysIncludeIndex', PHP_INT_MAX);
    }
  }
  
  /**
   * addTimberNamespaces
   *
   * Creates a Timber namespace for each folder within the twigs
   *
   * @param FilesystemLoader $loader
   *
   * @return FilesystemLoader
   * @throws LoaderError
   */
  protected function addTimberNamespaces(FilesystemLoader $loader): FilesystemLoader
  {
    $dir = $this->getStylesheetDir() . '/assets/twigs';
    $folders = array_filter(glob($dir . '/*'), 'is_dir');
    foreach ($folders as $folder) {
      
      // each folder within $folders runs from the root of the filesystem all
      // the way to the folders within the /assets/twigs folder.  all we want
      // are those folder names.  we explode them all, pop off the last bit,
      // and then use them as a namespace.  so, for example, the @templates
      // namespace will map to the /assets/twigs/templates folder.
      
      $namespaces = explode('/', $folder);
      $namespace = array_pop($namespaces);
      $loader->addPath($folder, $namespace);
    }
    
    return $loader;
  }
  
  /**
   * prepareTheme
   *
   * Specifies additional WordPress features that our theme supports as well
   * as registers menus.
   *
   * @return void
   */
  protected function prepareTheme(): void
  {
    register_nav_menus(['main' => 'Main Menu', 'footer' => 'Footer Menu']);
    add_theme_support('post-thumbnails', get_post_types(['public' => true]));
  }
  
  /**
   * preventCoreTemplateLoader
   *
   * Returns true the first time and false thereafter.  First time, this allows
   * the template_redirect actions to occur.  Subsequently, it'll prevent the
   * WP Core template loader from using up some server-side time executing the
   * Core router when we have our own as a part of our theme.
   *
   * @return bool
   */
  protected function preventCoreTemplateLoader(): bool
  {
    // this method needs to return true the first time and false thereafter.
    // we do this by leaving this property unset.  the first time we get here,
    // it is set and returns its new true value.  subsequently, it will have
    // already been set, and so the !isset test will short-circuit the AND
    // operation and we'll return false.
    
    return !isset($this->coreTemplateLoaderPrevention)
      && ($this->coreTemplateLoaderPrevention = true);
  }
  
  /**
   * almostAlwaysIncludeIndex
   *
   * Almost always includes the index file for this theme which handles the
   * routing operations.
   *
   * @return void
   */
  protected function almostAlwaysIncludeIndex(): void
  {
    if (is_trackback() || is_feed() || is_favicon() || is_robots()) {
      
      // the template-loader.php file checks for each of these four options
      // after it runs the template_redirect actions.  in each of these cases,
      // it halts the operation of that file after doing some additional work.
      // we don't want to impact that work, so we just return here.
      
      return;
    }
    
    // otherwise, because we've blocked the default WordPress template loader,
    // we want to include the index.php template file for our theme now because
    // we won't be relying on WP Core to do so for us.
    
    include locate_template('index.php');
  }
  
  /**
   * getPrefix
   *
   * Uses our SLUG constant to build a prefix for option names that we can
   * use throughout this theme.
   *
   * @return string
   */
  public static function getPrefix(): string
  {
    return self::SLUG . '-';
  }
}
