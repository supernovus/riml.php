<?php

namespace Riml;

/**
 * A response code.
 */
class ResponseCodes
{
  use RimlBase;

  /**
   * Properties allowed in ResponseCodes.
   */
  const RESPONSE_CODE_PROPS =
  [
    'description', 'success', 'bodySchema',
  ];
  
  public $success;
  public $bodySchema;

  public function __construct ($data, $parent)
  {
    $this->parent = $parent;
    $this->root   = $root;
    foreach (self::RESPONSE_CODE_PROPS as $pname)
    {
      if (isset($data[$pname]))
      {
        $this->$pname = $data[$pname];
      }
    }
  }
}

