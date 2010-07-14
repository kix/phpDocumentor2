<?php
class DocBlox_Arguments extends Zend_Console_Getopt
{
  protected $files = array();
  protected $allowed_extensions = array('php', 'php3', 'phtml');

  public function __construct()
  {
    parent::__construct(array(
      'filename|f=s' => 'name of file(s) to parse file1,file2. Can contain complete path and * ? wildcards',
      'directory|d=s' => 'name of a directory(s) to recursively parse directory1,directory2',
      'extensions|e-s' => 'optional comma-separated list of extensions to parse, defaults to php, php3 and phtml',
      'target|t-s' => 'path where to save the generated files (optional, defaults to "output")',
      'verbose|v' => 'Outputs any information collected by this application, may slow down the process slightly',
      'ignore|i-s' => 'file(s) that will be ignored, multiple separated by ",".  Wildcards * and ? are ok',
      'force' => 'forces a full build of the documentation, does not increment existing documentation',
    ));
  }

  protected function parseFiles()
  {
    $files = array();

    // read the filename argument in search for files (wildcards are explicitly allowed)
    $expressions = $this->getOption('filename') ? explode(',', $this->getOption('filename')) : array();
    foreach($expressions as $expr)
    {
      // search file(s) with the given expressions
      $result = glob($expr);

      foreach ($result as $key => $file)
      {
        $info = pathinfo($file);
        if (isset($info['extension']) && in_array(strtolower($info['extension']), $this->getExtensions()) )
        {
          continue;
        }

        // check if the given is a file
        if (!is_file($file))
        {
          unset($result[$key]);
        }
      }

      // add all legal files to the files array
      $files = array_merge($files, $result);
    }

    // get all directories which must be recursively checked
    $option_directories = $this->getOption('directory') ? explode(',', $this->getOption('directory')) : array();
    foreach ($option_directories as $directory)
    {
      // if the given is not a directory, skip it
      if (!is_dir($directory))
      {
        continue;
      }

      // get all files recursively to the files array
      $files_iterator = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
      /** @var SplFileInfo $file */
      foreach(new RecursiveIteratorIterator($files_iterator) as $file)
      {
        $info = pathinfo($file);
        if (!isset($info['extension']) || !in_array(strtolower($info['extension']), $this->getExtensions()))
        {
          continue;
        }

        $files[] = $file->getPathname();
      }
    }

    return $files;
  }

  public function getFiles()
  {
    if (!$this->files)
    {
      $this->files = $this->parseFiles();
    }

    return $this->files;
  }

  public function getExtensions()
  {
    if ($this->getOption('extensions'))
    {
      $this->allowed_extensions = explode(',', $this->getOption('extensions'));
    }

    return $this->allowed_extensions;
  }

  public function getTarget()
  {
    if (!$this->getOption('target'))
    {
      return './output';
    }

    $target = ltrim($this->getOption('target'), DIRECTORY_SEPARATOR);
    if ($target == '')
    {
      throw new Exception('Either an empty path or root was given');
    }

    return $target;
  }

  public function getIgnorePatterns()
  {
    if (!$this->getOption('ignore'))
    {
      return array();
    }

    return explode(',', $this->getOption('ignore'));
  }
}