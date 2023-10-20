<?php

namespace Dashifen;

use Dashifen\Dashifen2024\Theme;
use Dashifen\Exception\Exception;
use Dashifen\Dashifen2024\Agents\SilencingAgent;
use Dashifen\Dashifen2024\Agents\CoreRemovalAgent;
use Dashifen\Dashifen2024\Agents\HeadAndFootAgent;
use Dashifen\WPHandler\Agents\Collection\Factory\AgentCollectionFactory;

if (version_compare(PHP_VERSION, '8.2', '<')) {
  $message = 'This theme requires at least PHP 8.0; you\'re using %s.';
  die(sprintf($message, PHP_VERSION));
}

if (!class_exists(Theme::class)) {
  
  // if we don't already know our Theme object, then we must not have included
  // an autoloader.  therefore, we'll include the one that's adjacent to this
  // file which will make our theme's objects available to its other files.
  
  require 'vendor/autoload.php';
}

(function () {
  
  // initializing our theme in this anonymous function means that nothing that
  // we declare in this scope is available outside of it.  this should make it
  // so that our objects remain inaccessible except within the context of our
  // theme.
  
  try {
    $theme = new Theme();
    $acf = new AgentCollectionFactory();
    $acf->registerAgent(SilencingAgent::class);
    $acf->registerAgent(CoreRemovalAgent::class);
    $acf->registerAgent(HeadAndFootAgent::class);
    $theme->setAgentCollection($acf);
    $theme->initialize();
  } catch (Exception $e) {
    Theme::catcher($e);
  }
})();
