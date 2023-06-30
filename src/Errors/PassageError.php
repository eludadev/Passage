<?php

namespace Eludadev\Passage\Errors;

use InvalidArgumentException;

class PassageError extends \Exception
{
  /**
   * Create a new Passage error instance.
   *
   * @param string $message The error message
   * @param int $code The error code (optional)
   * @param \Throwable|null $previous The previous exception (optional)
   */
  public function __construct($message, $code = 0, \Throwable $previous = null)
  {
    parent::__construct($message, $code, $previous);
  }

  /**
   * Throw an invalid argument exception.
   *
   * @param string $message The error message
   * @param int $code The error code (optional)
   * @param \Throwable|null $previous The previous exception (optional)
   * @throws \InvalidArgumentException
   */
  public static function invalidArgument($message, $code = 0, \Throwable $previous = null)
  {
    throw new InvalidArgumentException($message, $code, $previous);
  }
}
