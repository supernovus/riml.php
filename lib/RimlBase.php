<?php

namespace Riml;

/**
 * A trait for properties common to all RIML classes.
 */
trait RimlBase
{
  /**
   * Human readable title for documentation.
   */
  public $title;
  /**
   * Human readable description for documentation.
   */
  public $description;
  /**
   * Parent object (if applicable.)
   */
  public $parent;
  /**
   * Root RIML object.
   */
  public $root;
}

