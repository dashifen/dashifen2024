<?php

namespace Dashifen;

use Dashifen\Dashifen2024\Theme;
use Dashifen\Dashifen2024\Router;
use Dashifen\Dashifen2024\Templates\Framework\TemplateFactory;
use Dashifen\Dashifen2024\Templates\Framework\TemplateException;

if (defined('ABSPATH')) {
  (function() {
    try {
      
      // unlike most classic WP themes, we only have an index.php.  this means
      // that we have to use it to identify the template object that we use to
      // produce the requested content.  our Router object will do so, and the
      // TemplateFactory will use the Router's work to produce the object we
      // need.  after that, we simply render that template adn we're done.
      
      $templateName = (new Router())->getTemplateObjectName();
      $templateObject = TemplateFactory::produceTemplate($templateName);
      $templateObject->render();
    } catch (TemplateException $e) {
      Theme::catcher($e);
    }
  })();
}