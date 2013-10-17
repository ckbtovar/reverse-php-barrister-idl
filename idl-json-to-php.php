<?php
(@include_once __DIR__ . '/vendor/autoload.php') || @include_once __DIR__ . '/../../autoload.php';
require __DIR__ . '/command/Batch.php';
require __DIR__ . '/command/Make.php';
require 'IdlParser.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use CreditKarma\Barrister\Tools\Idl2Php\Command\Batch;
use CreditKarma\Barrister\Tools\Idl2Php\Command\Make;

$console = new Application();
$console->add(new Batch());
$console->add(new Make());
$console->run();