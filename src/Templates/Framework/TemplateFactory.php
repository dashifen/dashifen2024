<?php

namespace Dashifen\Dashifen2023\Templates\Framework;

use Dashifen\WPDebugging\WPDebuggingTrait;

class TemplateFactory
{
  use WPDebuggingTrait;
  
  /**
   * produceTemplate
   *
   * Given the name of a template object, constructs and returns it.
   *
   * @param string $template
   *
   * @return AbstractTemplate
   * @throws TemplateException
   */
  public static function produceTemplate(string $template): AbstractTemplate
  {
    $namespaced = 'Dashifen\\Dashifen2023\\Templates\\' . $template;
    
    if (!class_exists($namespaced)) {
      $namespaced = 'Dashifen\\Dashifen2023\\Templates\\DefaultTemplate';
    }
    
    // we'll assume that, if it's a class that exists, $template refers to a
    // child of our AbstractTemplate object.  if not, PHP will help us fix the
    // problem when the return type hint fails.
    
    return new $namespaced;
  }
}
