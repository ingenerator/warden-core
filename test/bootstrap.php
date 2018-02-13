<?php
# Bootstrap for running unit tests
error_reporting(E_ALL | E_STRICT);

// Autoload mocks and test-support helpers that should not autoload in the main app
$mock_loader = new \Composer\Autoload\ClassLoader;
$mock_loader->addPsr4('test\\mock\\Ingenerator\\Warden\\Core\\', [__DIR__.'/mock/']);
$mock_loader->addPsr4('test\\unit\\Ingenerator\\Warden\\Core\\', [__DIR__.'/unit/']);
$mock_loader->addPsr4('test\\integration\\Ingenerator\\Warden\\Core\\', [__DIR__.'/integration/']);

$mock_loader->register();
