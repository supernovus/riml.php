<?php

namespace Riml;

/**
 * Routing Information Modeling Language
 *
 * A YAML-based format for describing routing information.
 * Can be used for several purposes. This libary is an implementation of the
 * core specification. 
 *
 * In addition to this PHP implementation, I am writing a Node.js one too.
 *
 * It's loosely inspired by RAML.
 */
class Parser
{
  use RouteInfo;

  /**
   * The RIML version.
   */
  const VERSION = '1.0-DRAFT-11';

  /**
   * The namespace RIML child classes are defined in.
   */
  const NS = "\\Riml\\";

  /**
   * Properties allowed in root document, and Route documents.
   */
  const COMMON_PROPS = 
  [
    'title', 'description', 'controller', 'method', 'apiType', 'authType',
  ];

  public $method_prefix = 'handle_';
  public $confdir;

  protected $traits = []; // Traits for later use.

  protected $included = []; // A list of files we've included.

  protected $sources = []; // Source files marked as .includePoly: true

  public function __construct ($source)
  {
    if (is_string($source))
    { // Assume it's the filename.
      $source = $this->loadFile($source);
    }
    elseif (is_array($source))
    {
      if (isset($source['dir']))
      {
        $this->confdir = $source['dir'];
      }
      if (isset($source['prefix']))
      {
        $this->method_prefix = $source['prefix'];
      }

      if (isset($source['file']))
      { // The filename was explicitly passed.
        $source = $this->loadFile($source['file']);
      }
      elseif (isset($source['text']))
      { // The RIML text was explicitly passed.
        $source = $this->loadText($source['text']);
      }
      elseif (isset($source['data']))
      { // The data was explicitly passed.
        $source = $source['data'];
      }
      else
      {
        throw new \Exception("Invalid named paramter sent to RIML() constructor.");
      }
    }
    else
    {
      throw new \Exception("Invalid data passed to RIML() constructor.");
    }
    $this->root   = $this;
    foreach (self::COMMON_PROPS as $pname)
    {
      if (isset($source[$pname]))
      {
        $this->$pname = $source[$pname];
        unset($source[$pname]);
      }
    }
    $this->addRoutes($source);
  }

  protected function loadFile ($filename)
  {
#    error_log("RIML::loadFile($filename)");
    if (file_exists($filename))
    {
      $confdir = dirname($filename);
      if (!isset($this->confdir))
      {
        $this->confdir = $confdir;
      }
      $text = file_get_contents($filename);
      return $this->loadText($text, $confdir);
    }
    throw new \Exception("Invalid filename '$filename' passed to RIML::loadFile()");
  }

  protected function loadText ($text, $confdir=null)
  {
    $self = $this;
    return yaml_parse($text, 0, $ndocs,
    [
      '!include' => function ($value, $tag, $flags) use ($self, $confdir)
      {
        return $self->includeFile($value, $confdir, true);
      },
      '!includePath' => function ($value, $tag, $flags) use ($self, $confdir)
      {
        return $self->includeFile($value, $confdir, false);
      },
      '!define' => function ($value, $tag, $flags) use ($self)
      {
        return $self->defineMetadata($value);
      },
      '!use' => function ($value, $tag, $flags) use ($self)
      {
        return $self->useMetadata($value);
      },
      '!controller' => function ($value, $tag, $flags)
      {
        if (!is_array($value))
          $value = [];
        $value['.controller'] = true;
        return $value;
      },
      '!method' => function ($value, $tag, $flags)
      {
        if (!is_array($value))
          $value = [];
        $value['.method'] = true;
        return $value;
      },
      '!virtual' => function ($value, $tag, $flags)
      {
        if (!is_array($value))
          $value = [];
        $value['virtual'] = true;
        return $value;
      },
    ]);
  }

  protected function includeFile ($file, $confdir, $setNoPath)
  {
#    error_log("RIML::includeFile(\"$file\", ".($setNoPath?'true':'false').')');
    if (!isset($confdir))
    { // No specific confdir passed for the current context, check for global.
      $confdir = $this->confdir;
    }

    if (isset($confdir) && substr($file, 0, 1) !== '/')
    { // The filename is relative to the current confdir.
      $file = $confdir . '/' . $file;
    }

    if (isset($this->included[$file]))
    {
      if ($this->included[$file])
        return null;
      elseif (isset($this->sources[$file]))
        return $this->sources[$file];
    }
    $yaml = $this->loadFile($file);
    $mark = true;
    if (isset($yaml) && is_array($yaml))
    { // Included files are assumed to be virtual by default.
      if (!isset($yaml['virtual']))
        $yaml['virtual'] = true;
      if ($setNoPath && !isset($yaml['noPath']))
        $yaml['noPath'] = true;
      if (isset($yaml['.includePoly']) && $yaml['.includePoly'])
      {
        $mark = false;
        $this->sources[$file] = $yaml;
      }
    }
    $this->included[$file] = $mark;
    return $yaml;
  }

  protected function defineMetadata ($data)
  {
#    error_log("RIML::defineMetadata(".json_encode($data).")");
    if (is_array($data) && isset($data['.trait']))
    {
      $name = $data['.trait'];
      unset($data['.trait']);
      $this->traits[$name] = $data;
    }
  }

  protected function useMetadata ($data)
  {
#    error_log("RIML::useMetadata(".json_encode($data).")");
    if (is_array($data) && isset($data['.traits']))
    {
      $traits = $data['.traits'];
      unset($data['.traits']);
      if (!is_array($traits))
        $traits = [$traits];
      foreach ($traits as $tname)
      {
        if (isset($this->traits[$tname]))
        {
          $trait = $this->traits[$tname];
          if (is_array($trait))
          {
            $consumed = [];
            foreach ($trait as $tpname => $tpval)
            {
              if ($tpname == '.placeholders') continue; // skip placeholders.
              if ($tpname == '.vars' && is_array($tpval))
              {
                if (isset($data[$tpname]) && is_array($data[$tpname]))
                {
                  $data[$tpname] += $tpval;
                }
                else
                {
                  $data[$tpname] = $tpval;
                }
              }
              else
              {
                if (!isset($data[$tpname]))
                {
                  $data[$tpname] = $tpval;
                  $consumed[$tpname] = true;
                }
                else
                {
                  $consumed[$tpname] = false;
                }
              }
            }
            $this->handlePlaceholders($data, $trait, $consumed);
          }
        }
      }
    }
    return $data;
  }

  protected function handlePlaceholders (&$data, $trait, $consumed)
  {
    if (isset($trait['.placeholders']))
    { // Expand variables.
      $vars = $trait['.placeholders'];
      foreach ($vars as $varname => $varpathspec)
      {
        if ($varname == '.vars') continue; // sanity check.
        if (isset($data['.vars'], $data['.vars'][$varname]))
        {
          $value = $data['.vars'][$varname];
  
          if (is_string($varpathspec))
            $varpathspec = [$varpathspec];
  
          foreach ($varpathspec as $varpath)
          {
            $varpaths = explode('|', trim($varpath, '|'));
#            error_log("varpaths: ".json_encode($varpaths));
            // A quick sanity check.
            $firstitem = $varpaths[0];
            if (isset($consumed[$firstitem]) && !$consumed[$firstitem])
            { // This path was seen, but not consumed, skip it.
              continue;
            }
            $tdata = &$data;
            $lastitem = array_pop($varpaths);
            $textitem = null;
            foreach ($varpaths as $vp)
            {
#              error_log("tdata: ".json_encode($tdata));
              if (is_array($tdata) && isset($tdata[$vp]))
              {
                if (is_array($tdata[$vp]))
                {
                  $tdata = &$tdata[$vp];
                }
                elseif (is_string($tdata[$vp]))
                {
                  $textitem = $vp;
                  break;
                }
              }
              else
              {
                throw new \Exception("Invalid variable path '$varpath', '$vp' is missing.");
              }
            }
            if (isset($textitem))
            {
              $tdata[$textitem] = str_replace($lastitem, $value, $tdata[$textitem]);
            }
            elseif (is_array($tdata) && isset($tdata[$lastitem]))
            {
              $tdata[$lastitem] = $value;
            }
          }
        }
        else
        {
          throw new \Exception("Unfulfilled variable '$varname' in use statement.");
        }
      }
    }
  }

  public function version ()
  {
    return self::VERSION;
  }

}

