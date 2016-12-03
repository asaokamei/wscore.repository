<?php
namespace WScore\Repository\Helpers;

use InvalidArgumentException;
use Interop\Container\Exception\ContainerException;

class ContainerErrorException extends InvalidArgumentException implements ContainerException
{
}