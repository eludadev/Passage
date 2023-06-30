<?php

namespace Eludadev\Passage;

use Illuminate\Support\Facades\Http;
use Eludadev\Passage\Errors\PassageError;
use Firebase\JWT\CachedKeySet;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Firebase\JWT\JWT;
use GuzzleHttp\Psr7\HttpFactory;
use Phpfastcache\CacheManager;

/**
 * Fetches the JWKS (JSON Web Key Set) from the provided URL.
 *
 * @param string $url The URL to fetch the JWKS from.
 * @return CachedKeySet The JWKS data.
 * @throws PassageError if the JWKS fetching fails.
 */
function createRemoteJWKSet($url): CachedKeySet
{
  // Create an HTTP client (can be any PSR-7 compatible HTTP client)
  $httpClient = new Client();

  // Create an HTTP request factory (can be any PSR-17 compatible HTTP request factory)
  $httpFactory = new HttpFactory();

  // Create a cache item pool (can be any PSR-6 compatible cache item pool)
  $cacheItemPool = CacheManager::getInstance('files');

  $keySet = new CachedKeySet(
    $url,
    $httpClient,
    $httpFactory,
    $cacheItemPool,
    null, // $expiresAfter int seconds to set the JWKS to expire
    true  // $rateLimit    true to enable rate limit of 10 RPS on lookup of invalid keys
  );

  return $keySet;
}

class Passage
{
  /**
   * The Passage application ID.
   *
   * @var string
   */
  private $appId;

  /**
   * The Passage API key.
   *
   * @var string
   */
  private $apiKey;

  /**
   * The JWKS (JSON Web Key Set) for authentication.
   *
   * @var CachedKeySet
   */
  private $jwks;

  /**
   * The authentication strategy.
   *
   * @var string
   */
  private $authStrategy;

  /**
   * The User object for accessing User-related functionality.
   *
   * @var \Eludadev\Passage\User
   */
  public $user;

  /**
   * Create a new Passage instance.
   *
   * @param string $appId
   * @param string $apiKey
   * @param string $authStrategy (optional)
   * @return void
   */
  public function __construct(string $appId, string $apiKey, string $authStrategy = 'COOKIE')
  {
    // Store the app ID and API key in private variables
    $this->appId = $appId;
    $this->apiKey = $apiKey;

    // Initialize the JWKS URL and authentication strategy
    $this->authStrategy = $authStrategy;

    // Initialize the User object
    $this->user = new User($appId, $apiKey);

    $jwksUrl = "https://auth.passage.id/v1/apps/{$appId}/.well-known/jwks.json";
    $this->jwks = createRemoteJWKSet($jwksUrl);
  }

  /**
   * Get App Info about an app
   *
   * @return array Passage App object
   * @throws PassageError
   */
  public function getApp(): array
  {
    // Construct the URL for the Passage API endpoint
    $url = 'https://api.passage.id/v1/apps/' . $this->appId;

    // Set the headers for the API request
    $headers = [
      'Authorization' => 'Bearer ' . $this->apiKey,
    ];

    // Send the HTTP GET request to the Passage API
    $response = Http::withHeaders($headers)->get($url);

    // Check if the request was successful
    if ($response->successful()) {
      $responseData = $response->json();
      $appData = $responseData['app'];

      return $appData;
    } else {
      // Throw a PassageError or handle the failure as needed
      throw new PassageError('Could not fetch app.');
    }
  }

  /**
   * Create a magic link for user authentication.
   *
   * @param string $email
   * @param string $redirectUrl
   * @return array The generated magic link data
   *
   * @throws \Exception
   */
  public function createMagicLink(string $email, string $redirectUrl): array
  {
    // Construct the URL for the Passage API endpoint
    $url = 'https://api.passage.id/v1/apps/' . $this->appId . '/magic-links';

    // Set the headers for the API request
    $headers = [
      'Authorization' => 'Bearer ' . $this->apiKey,
      'Content-Type' => 'application/json',
    ];

    // Prepare the data payload for the API request
    $data = [
      'email' => $email,
      'redirect_url' => $redirectUrl,
    ];

    // Send the HTTP POST request to the Passage API
    $response = Http::withHeaders($headers)
      ->post($url, $data);

    // Extract the 'magic_link' key from the JSON response
    $responseData = $response->json();
    $magicLink = $responseData['magic_link'];

    // Return the magic link
    return $magicLink;
  }

  /**
   * Authenticate request with a cookie, or header. If no authentication
   * strategy is given, authenticate the request via cookie (default
   * authentication strategy).
   *
   * @param Request $request Laravel request
   * @return string UserID of the Passage user
   * @throws PassageError if the authentication strategy is invalid
   */
  public function authenticateRequest(Request $request): string
  {
    if ($this->authStrategy === 'HEADER') {
      return $this->authenticateRequestWithHeader($request);
    } else {
      return $this->authenticateRequestWithCookie($request);
    }
  }

  /**
   * Authenticate a request via the HTTP header.
   *
   * @param Request $request Laravel request
   * @return string User ID for Passage User
   * @throws PassageError if the authorization header is not found or the auth token is invalid
   */
  private function authenticateRequestWithHeader(Request $request): string
  {
    $authorization = $request->header('Authorization');
    if (!$authorization) {
      throw new PassageError('Header authorization not found. You must catch this error.');
    } else {
      $authToken = explode(' ', $authorization)[1];
      $userID = $this->validAuthToken($authToken);
      if ($userID) {
        return $userID;
      } else {
        throw new PassageError('Auth token is invalid');
      }
    }
  }

  /**
   * Authenticate request via cookie.
   *
   * @param Request $req The HTTP request object
   * @return string User ID for Passage User
   * @throws PassageError If a valid cookie for authentication is not found
   */
  private function authenticateRequestWithCookie(Request $req): string
  {
    $cookiesStr = $req->header('cookie');
    if (!$cookiesStr) {
      throw new PassageError('Could not find valid cookie for authentication. You must catch this error.');
    }

    $cookies = explode(';', $cookiesStr);
    $passageAuthToken = null;

    foreach ($cookies as $cookie) {
      $cookieParts = explode('=', $cookie);
      $key = trim($cookieParts[0]);

      if ($key === 'psg_auth_token') {
        $passageAuthToken = trim($cookieParts[1]);
        break;
      }
    }

    if ($passageAuthToken) {
      $userID = $this->validAuthToken($passageAuthToken);

      if ($userID) {
        return $userID;
      } else {
        throw new PassageError('Could not validate auth token. You must catch this error.');
      }
    } else {
      throw new PassageError("Could not find authentication cookie 'psg_auth_token' token. You must catch this error.");
    }
  }

  /**
   * Determine if the provided token is valid when compared with its
   * respective public key.
   *
   * @param string $token The authentication token
   * @return string|null The sub claim if the JWT can be verified, or null
   */
  private function validAuthToken($token)
  {
    try {
      $decodedHeader = JWT::urlsafeB64Decode(explode('.', $token)[0]);
      $header = json_decode($decodedHeader, true);
      $kid = $header['kid'];


      if (!$kid) {
        // If the 'kid' is missing, the token cannot be verified
        return null;
      }

      $decodedToken = JWT::decode($token, $this->jwks);
      $userID = $decodedToken->sub;

      if ($userID) {
        // If the 'sub' claim exists, return it as a string
        return strval($userID);
      } else {
        // If the 'sub' claim is missing, the token cannot be verified
        return null;
      }
    } catch (\Exception $e) {
      // An exception occurred during token verification
      return null;
    }
  }
}
