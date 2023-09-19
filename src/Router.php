<?php

namespace Dashifen\Dashifen2024;

use Dashifen\WPDebugging\WPDebuggingTrait;
use Dashifen\WPHandler\Traits\CaseChangingTrait;

class Router
{
  use CaseChangingTrait;
  use WPDebuggingTrait;
  
  /**
   * getTemplateObjectName
   *
   * Uses WordPress core functions to identify what template to use based
   * on core's understanding of what content we're loading.
   *
   * @return string
   */
  public function getTemplateObjectName(): string
  {
    return match (true) {
      is_front_page() => 'HomepageTemplate',
      is_singular()   => $this->getPostTypeTemplate(),
      is_404()        => 'FourOhFourTemplate',
      default         => 'DefaultTemplate',
    };
  }
  
  /**
   * getPostTypeTemplate
   *
   * Returns the name of the template to use for the current post type.
   *
   * @return string
   */
  private function getPostTypeTemplate(): string
  {
    return $this->kebabToPascalCase(get_post_type()) . 'Template';
  }
}
