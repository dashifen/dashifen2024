<?php

namespace Dashifen\Dashifen2024\Services;

use Dashifen\Composer\BumperException;
use Dashifen\Composer\Bumper as BaselineBumper;

class Bumper extends BaselineBumper
{
  /**
   * calculateNextVersion
   *
   * Uses the name of the current branch to determine the next version number
   * after the current one.
   *
   * @return void
   * @throws BumperException
   */
  protected function calculateNextVersion(): void
  {
    try {
      parent::calculateNextVersion();
    } catch (BumperException $e) {
      
      if (
        $e->getCode() === BumperException::INVALID_BRANCH
        && 'main' === (string) $this->getGitBranch()
      ) {
        
        // if we've encountered an invalid branch and if that branch is the
        // main branch, then we'll increase the build number.  this is likely
        // only important for pre-1.0 bumps, but it seems like it could be
        // useful otherwise, too.
        
        $this->doBuildCalculation();
      } else {
        
        // if we didn't calculate the next build in the if-block, all we can
        // do is re-throw the exception.
        
        throw $e;
      }
    }
  }
}