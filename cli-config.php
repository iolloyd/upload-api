<?php

use Doctrine\ORM\Tools\Console\ConsoleRunner;
require_once "bootstrap_doctrine.php";

return ConsoleRunner::createHelperSet($entityManager);
