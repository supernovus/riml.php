<?php

namespace Riml;

class Route
{
  use RouteInfo;

  /**
   * Properties allowed in Route documents.
   */
  const ROUTE_PROPS =
  [
    'name', 'path', 'http', 'responseSchema', 'requestSchema', 'responseCodes',
    'pathParams', 'queryParams', 'headers', 'tests', 'examples',
    'defaultRoute', 'redirect', 'redirectRoute',
    'virtual', 'noPath',
  ];

  /**
   * Route Properties that are a map of objects.
   */
  const ROUTE_OBJECT_MAP =
  [
    'pathParams'    => 'Param',
    'queryParams'   => 'Param',
    'headers'       => 'Param',
    'responseCodes' => 'ResponseCodes',
  ];

  /**
   * Route Properties that are an array of objects.
   */
  const ROUTE_OBJECT_ARRAY =
  [
    'tests'    => 'Test',
    'examples' => 'Example',
  ];

  /**
   * The internal identifier.
   */
  public $route_name;
  /**
   * The name of the route (used by the Router plugin.)
   */
  public $name;
  /**
   * The path we are testing against. 
   * Use {placeholder} style for placeholders.
   */
  public $path;
  /**
   * The HTTP Methods used by this route.
   * Set to false to use the parent route path.
   */
  public $http;
  /**
   * If this is true, then this route will not be added to the Router
   * list, but will be used to generate sub-routes using a set of default
   * options.
   */
  public $virtual = false;
  /**
   * If this is true, then we don't automatically derive a path name from
   * the route identifier.
   */
  public $noPath = false;
  /**
   * The return value should match this schema.
   */
  public $responseSchema;
  /**
   * If we are a PUT, POST, or PATCH, the body should match this schema.
   */
  public $requestSchema;
  /**
   * Path parameters.
   */
  public $pathParams;
  /**
   * Query string parameters.
   */
  public $queryParams;
  /**
   * Custom headers.
   */
  public $headers;
  /**
   * Test definitions.
   */
  public $tests;
  /**
   * Documentation examples.
   */
  public $examples;
  /**
   * This is the default route.
   */
  public $defaultRoute = false;
  /**
   * This route redirects here.
   */
  public $redirect;
  /**
   * The redirect is a Route name not a URL.
   */
  public $redirectRoute = false;

  public function __construct ($rname, $rdef, $parent)
  {
    if (!is_array($rdef))
    {
      $rdef = [];
    }

    $this->parent     = $parent;
    $this->root       = $parent->root;
    $this->route_name = $rname;

    foreach ([Parser::COMMON_PROPS, self::ROUTE_PROPS] as $psrc)
    {
      foreach ($psrc as $pname)
      {
        if (isset($rdef[$pname]))
        {
          if (isset(self::ROUTE_OBJECT_MAP[$pname]))
          {
            $classname = Parser::NS . self::ROUTE_OBJECT_MAP[$pname];
            $this->$pname = [];
            foreach ($rdef[$pname] as $mapkey => $mapval)
            {
              $this->$pname[$mapkey] = new $classname($mapval, $this);
            }
          }
          elseif (isset(self::ROUTE_OBJECT_ARRAY[$pname]))
          {
            $classname = Parser::NS . self::ROUTE_OBJECT_ARRAY[$pname];
            $this->$pname = [];
            foreach ($rdef[$pname] as $arrayval)
            {
              $this->$pname[] = new $classname($arrayval, $this);
            }
          }
          else
          {
            $this->$pname = $rdef[$pname];
          }
          unset($rdef[$pname]);
        }
      }
    }

    if (isset($rdef['.controller']) && $rdef['.controller'] && !isset($this->controller))
    {
      $this->controller = $this->autoName($rname, 'controller');
    }
    elseif (isset($rdef['.method']) && $rdef['.method'] && !isset($this->method))
    {
      $this->method = $this->autoName($rname, 'method');
    }

    if (!isset($this->path) && !$this->noPath)
    {
      $this->path = $rname;
    }

    foreach ($this->root->http_props as $hname)
    {
      if (isset($rdef[$hname]))
      {
        if (!is_array($rdef[$hname]))
          $rdef[$hname] = [];
        $rdef[$hname]['http'] = $hname;
        if (!isset($rdef[$hname]['path']))
          $rdef[$hname]['path'] = false; // Force parent path use.
      }
    }
    foreach ($this->root->api_props as $aname)
    {
      if (isset($rdef[$aname]))
      {
        if (!is_array($rdef[$aname]))
          $rdef[$aname] = [];
        $rdef[$aname]['apiType'] = $aname;
        if (!isset($rdef[$aname]['path']))
          $rdef[$aname]['path'] = false;
      }
    }

    $this->addRoutes($rdef);
  }

  protected function autoName($rname, $prop)
  {
    $root = $this->root;

    $o = function($what) use ($prop, $root)
    {
      $key = "{$prop}_{$what}";
      return $root->$key;
    };

    $pf = $o('prefix');
    $sf = $o('suffix');
    $rep = $o('replace');
    $camel = $o('camel');

    $aname = trim($rname, '/');
    $aname = preg_replace("/\W+/", $rep, $aname);
    $aname = $pf . $aname . $sf;
    $aname = $camel ? $this->camelCase($aname, $rep) : strtolower($aname);

    return $aname;
  }

  public static function camelCase($name, $sep='_', $notFirst=true)
  {
    $parts = explode($sep, $name);
    $cc = '';
    foreach ($parts as $p => $part)
    {
      if ($p == 0)
        $cc .= strtolower($part);
      else
        $cc .= ucfirst(strtolower($part));
    }
    return $cc;
  }

}

