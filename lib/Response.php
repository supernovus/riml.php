<?php

namespace Riml;

/**
 * A Response for an Example or Test
 */
class Response
{
  use RimlBase;

  /**
   * Properties allowed in Response documents.
   */
  const REPSONSE_PROPS =
  [
    'code', 'body', 'type', 'class',
  ];

  public $code;
  public $body;
  public $type;
  public $class;

  public function __construct ($data, $parent)
  {
    $this->parent = $parent;
    $this->root   = $parent->root;

    foreach (self::RESPONSE_PROPS as $pname)
    {
      if (isset($data[$pname]))
      {
        $this->$pname = $data[$pname];
      }
    }
  }
}

