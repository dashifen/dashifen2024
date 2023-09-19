<?php

namespace Dashifen\Dashifen2024\Templates;

use Dashifen\Dashifen2024\Templates\Framework\AbstractTemplate;

class DefaultTemplate extends AbstractTemplate
{
  /**
   * getPageContext
   *
   * Returns an array of information necessary for the compilation of a
   * specific twig template.
   *
   * @param array $siteContext
   *
   * @return array
   */
  protected function getPageContext(array $siteContext): array
  {
    return [
      'title'   => get_the_title(),
      'content' => apply_filters('the_content', get_the_content()),
    ];
  }
}
