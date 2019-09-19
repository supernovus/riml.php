<?php

namespace Riml;

/**
 * A trait for things common to the Parser class, and Route class.
 */
trait RouteInfo
{
  use RimlBase;

  /**
   * Controller name.
   */
  public $controller;
  /**
   * Handler method name.
   */
  public $method;
  /**
   * API type
   *
   *  false   Not used as an API.
   *  'json'  Uses JSON for API calls.
   *  'xml'   Uses XML for API calls.
   *  'text'  Uses and/or expects plain text for API calls.
   *  true    Uses Content-Type header to determine format.
   */
  public $apiType;
  /**
   * Authentication type
   *
   *  false        Doesn't require authentication.
   *  'userOnly'   Uses SimpleAuth users only (default if apiType false)
   *  'userAccess' Uses SimpleAuth passthrough.
   *  'ipAccess'   Uses ipAccess Authentication plugin.
   *  'token'      Uses App/Auth tokens plugin.
   *  true         Same as setting ["userAccess","ipAccess","token"]
   *
   * Use an array for multiple types if more than one is supported.
   */
  public $authType;

  /**
   * The routes within this structure.
   */
  protected $routes = [];

  /**
   * Options are defined using properties starting with a dot.
   */
  public $options = [];

  protected function addRoutes ($routes)
  {
    foreach ($routes as $rname => $rdef)
    {
      if (is_null($rdef)) continue;
      if (substr($rname, 0, 1) === '.')
      {
        $oname = substr($rname, 1);
        $this->options[$oname] = $rdef;
        continue;
      }
      $route = new Route($rname, $rdef, $this);
      $this->routes[] = $route;
    }
  }

  public function getRoutes ()
  {
    return $this->routes;
  }

  public function hasRoutes ()
  {
    return (count($this->routes) > 0);
  }

}

