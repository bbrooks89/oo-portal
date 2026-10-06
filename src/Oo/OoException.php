<?php
declare(strict_types=1);

namespace App\Oo;

/**
 * Thrown when a call to OO fails: network errors, HTTP errors, or timeouts.
 * Extending RuntimeException is like deriving from Exception in C#.
 */
class OoException extends \RuntimeException
{
}
