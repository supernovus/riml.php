<?php

namespace Riml;

/**
 * A shared class for Query Parameters and HTTP Headers.
 */
class Param
{
  use RimlBase;

  /**
   * Properties allowed in Parameters (queryParams, pathParams, headers.)
   */
  const PARAM_PROPS =
  [
    'title', 'description', 'type', 'required', 'multiple',
  ];

  public $type;
  public $required = false;
  public $multiple = false;

  public function __construct ($data, $parent)
  {
    $this->parent = $parent;
    $this->root   = $parent->root;
    foreach (self::PARAM_PROPS as $pname)
    {
      if (isset($data[$pname]))
      {
        $this->$pname = $data[$pname];
      }
    }
  }
}

