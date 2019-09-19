<?php

namespace Riml;

/**
 * A Request for an Example or Test
 */
class Request
{
  use RimlBase;

  /**
   * Properties allowed in Request documents.
   */
  const REQUEST_PROPS =
  [
    'http', 'body', 'pathParams', 'queryParams', 'headers', 'apiType', 'authType'
  ];

  public $http;
  public $body;
  public $queryParams;
  public $pathParams;
  public $headers;
  public $authType;
  public $apiType;

  public function __construct ($data, $parent)
  {
    $this->parent = $parent;
    $this->root   = $parent->root;

    foreach (self::REQUEST_PROPS as $pname)
    {
      if (isset($data[$pname]))
      {
        $this->$pname = $data[$pname];
      }
    }
  }
}

