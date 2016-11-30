<?php
namespace WScore\Repository\Helpers;

use InvalidArgumentException;
use Interop\Container\Exception\ContainerException as ExceptionInterface;

class ContainerException extends InvalidArgumentException implements ExceptionInterface
{
}