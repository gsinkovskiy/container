<?php
declare(strict_types=1);

namespace HazyCastrel\Container;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends \Exception implements NotFoundExceptionInterface
{

}