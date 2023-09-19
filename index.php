<?php

namespace Dashifen;

use Dashifen\Dashifen2023\Theme;
use Dashifen\Dashifen2023\Router;
use Dashifen\Dashifen2023\Templates\Framework\TemplateFactory;
use Dashifen\Dashifen2023\Templates\Framework\TemplateException;

if (defined('ABSPATH')) {
  (function() {
    try {
      
      // essentially to see if we could do it, we are ignoring the core
      // WordPress router in favor of using our own to identify template object
      // names.  realistically, this shouldn't be done because it wastes some
      // time as Core attempts to identify the template we should use only for
      // us to do so again.  but, it was certainly a fun challenge, and it
      // makes our theme look so much cleaner than it would otherwise.
      
      $templateName = (new Router())->getTemplateObjectName();
      $templateObject = TemplateFactory::produceTemplate($templateName);
      $templateObject->render();
    } catch (TemplateException $e) {
      Theme::catcher($e);
    }
  })();
}