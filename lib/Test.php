<?php

namespace Riml;

/**
 * A Test for a Route.
 */
class Test extends Example
{
  /**
   * Properties allowed in Test documents (in addition to Example properties).
   */
  const TEST_PROPS =
  [
    'validateRequest', 'validateResponse', 'authOptions',
  ];

  public $validateRequest  = false;
  public $validateResposne = false;
  public $authOptions;

  protected function get_props ()
  {
    return array_merge(self::EXAMPLE_PROPS, self::TEST_PROPS);
  }
}

