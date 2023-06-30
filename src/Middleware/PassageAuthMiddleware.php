<?php

namespace Eludadev\Passage\Middleware;

use Closure;
use Eludadev\Passage\Errors\PassageError;
use Eludadev\Passage\Passage;

/**
 * Class PassageAuthMiddleware
 *
 * This middleware provides authentication using Passage for incoming requests.
 */
class PassageAuthMiddleware
{
  /**
   * The Passage application ID.
   *
   * @var string
   */
  protected $appId;

  /**
   * The Passage API key.
   *
   * @var string
   */
  protected $apiKey;

  /**
   * Create a new PassageAuthMiddleware instance.
   *
   * @param string $appId
   * @param string $apiKey
   * @return void
   */
  public function __construct(string $appId, string $apiKey)
  {
    $this->appId = $appId;
    $this->apiKey = $apiKey;
  }

  /**
   * Handle an incoming request.
   *
   * @param mixed $request
   * @param \Closure $next
   * @return mixed
   *
   * @throws \Eludadev\Passage\Errors\PassageError
   */
  public function handle($request, Closure $next)
  {
    try {
      $passage = new Passage($this->appId, $this->apiKey);
      $userID = $passage->authenticateRequest($request);

      if ($userID) {
        // User authenticated
        $request->userID = $userID;
        return $next($request);
      }
    } catch (\Exception $e) {
      // Failed to authenticate
      // We recommend returning a 401 or other "unauthorized" behavior
      throw new PassageError('Could not authenticate user!', 401);
    }
  }
}
