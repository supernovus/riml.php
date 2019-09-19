<?php

namespace Riml;

/**
 * Example routes.
 */
class Example
{
  use RimlBase;

  /**
   * Properties allowed in Example documents.
   */
  const EXAMPLE_PROPS =
  [
    'title', 'description', 'request', 'response',
  ];

  /**
   * Example/Test properties that are nested objects.
   */
  const EXAMPLE_OBJECTS =
  [
    'request'  => 'Request',
    'response' => 'Response',
  ];

  public $request;
  public $response;

  protected function get_props ()
  {
    return self::EXAMPLE_PROPS;
  }

  public function __construct ($data, $parent)
  {
    $this->parent = $parent;
    $this->root   = $parent->root;

    $props = $this->get_props();

    foreach ($props as $pname)
    {
      if (isset($data[$pname]))
      {
        if (isset(EXAMPLE_OBJECTS[$pname]))
        {
          $classname = Parser::NS . self::EXAMPLE_OBJECTS[$pname];
          $this->$pname = new $classname($data[$pname], $this);
        }
        else
        {
          $this->$pname = $data[$pname];
        }
      }
    }
  }
}

