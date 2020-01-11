<?php

require '../../../vendor/autoload.php';

use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Saia\core\DatabaseConnection;
use Symfony\Component\Console\Helper\HelperSet;

$Connection = DatabaseConnection::getInstance();

return new HelperSet([
    'db' => new ConnectionHelper($Connection)
]);
