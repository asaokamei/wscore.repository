<?php
namespace WScore\Repository\Helpers;

use InvalidArgumentException;
use Interop\Container\Exception\NotFoundException;

class ContainerNotFoundException extends InvalidArgumentException implements NotFoundException
{
}