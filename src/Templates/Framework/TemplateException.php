<?php

namespace Dashifen\Dashifen2023\Templates\Framework;

use Dashifen\WPTemplates\TemplateException as BaselineTemplateException;

class TemplateException extends BaselineTemplateException
{
  // the baseline template exception has some constants defined in its scope,
  // but it doesn't have 97 of them.  therefore, in honor of the finest band
  // east of all points west and to avoid colliding with the baseline constant
  // values, we start there.
  
  public const UNKNOWN_TWIG     = 97;
  public const UNKNOWN_CONTEXT  = 98;
  public const UNKNOWN_TEMPLATE = 99;
}
