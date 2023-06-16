<?php

function testSetup()
{
  $root = realpath(dirname(__FILE__).'/../') . '/_storage';
  if (is_dir($root)) {
    exec('rm -Rf '. escapeshellarg($root).'/*');
  }

  require_once(dirname(__FILE__). '/mocks.php');
  require_once(dirname(__FILE__). '/../../src/driver.php');

  return $root;
}
