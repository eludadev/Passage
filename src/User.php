<?php

namespace Eludadev\Passage;

use DateTime;
use Eludadev\Passage\Errors\PassageError;
use Illuminate\Support\Facades\Http;

class User
{
    private string $appId;
    private string $apiKey;

    /**
     * User constructor.
     *
     * @param string $appId The Passage application ID.
     * @param string $apiKey The Passage API key.
     */
    public function __construct(string $appId, string $apiKey)
    {
        $this->appId = $appId;
        $this->apiKey = $apiKey;
    }

    /**
     * Retrieve the list of devices for a user.
     *
     * @param string $userId The user ID.
     * @return array The list of user devices.
     * @throws PassageError
     */
    public function listDevices(string $userId): array
    {
        // Construct the URL for the Passage API endpoint
        $url = 'https://api.passage.id/v1/apps/' . $this->appId . '/users/' . $userId . '/devices';

        // Set the headers for the API request
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ];

        // Send the HTTP GET request to the Passage API
        $response = Http::withHeaders($headers)->get($url);

        // Extract the 'devices' array from the JSON response
        $responseData = $response->json();
        $devices = $responseData['devices'];

        // Return the list of user devices
        return $devices;
    }

    /**
     * Revoke a device for a user.
     *
     * @param string $userId The user ID.
     * @param string $deviceId The device ID.
     * @return bool True if the device revocation was successful; otherwise, false.
     * @throws PassageError
     */
    public function revokeDevice(string $userId, string $deviceId): bool
    {
        // Construct the URL for the Passage API endpoint
        $url = 'https://api.passage.id/v1/apps/' . $this->appId . '/users/' . $userId . '/devices/' . $deviceId;

        // Set the headers for the API request
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ];

        // Send the HTTP DELETE request to the Passage API
        $response = Http::withHeaders($headers)->delete($url);

        // Check if the request was successful
        if ($response->successful()) {
            return true;
        } else {
            // Throw a PassageError or handle the failure as needed
            throw new PassageError('Failed to revoke device for the user.');
        }
    }

    /**
     * Get information about a user.
     *
     * @param string $userId The user ID.
     * @return array The user information.
     * @throws PassageError
     */
    public function get(string $userId): array
    {
        // Construct the URL for the Passage API endpoint
        $url = 'https://api.passage.id/v1/apps/' . $this->appId . '/users/' . $userId;

        // Set the headers for the API request
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ];

        // Send the HTTP GET request to the Passage API
        $response = Http::withHeaders($headers)->get($url);

        // Check if the request was successful
        if ($response->successful()) {
            $responseData = $response->json();
            $user = $responseData['user'];

            // Parse created_at and last_login_at fields into DateTime objects
            $user['created_at'] = new DateTime($user['created_at']);
            $user['last_login_at'] = new DateTime($user['last_login_at']);

            return $user;
        } else {
            // Throw a PassageError or handle the failure as needed
            throw new PassageError('Failed to retrieve user information.');
        }
    }

    /**
     * Deactivate a user.
     *
     * @param string $userId The user ID.
     * @return array The deactivated user information.
     * @throws PassageError
     */
    public function deactivate(string $userId): array
    {
        // Construct the URL for the Passage API endpoint
        $url = 'https://api.passage.id/v1/apps/' . $this->appId . '/users/' . $userId . '/deactivate';

        // Set the headers for the API request
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ];

        // Send the HTTP PATCH request to the Passage API
        $response = Http::withHeaders($headers)->patch($url);

        // Check if the request was successful
        if ($response->successful()) {
            $responseData = $response->json();
            $user = $responseData['user'];

            // Parse created_at and last_login_at fields into DateTime objects
            $user['created_at'] = new DateTime($user['created_at']);
            $user['last_login_at'] = new DateTime($user['last_login_at']);

            return $user;
        } else {
            // Throw a PassageError or handle the failure as needed
            throw new PassageError('Failed to deactivate the user.');
        }
    }

    /**
     * Activate a user.
     *
     * @param string $userId The user ID.
     * @return array The activated user information.
     * @throws PassageError
     */
    public function activate(string $userId): array
    {
        // Construct the URL for the Passage API endpoint
        $url = 'https://api.passage.id/v1/apps/' . $this->appId . '/users/' . $userId . '/activate';

        // Set the headers for the API request
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ];

        // Send the HTTP PATCH request to the Passage API
        $response = Http::withHeaders($headers)->patch($url);

        // Check if the request was successful
        if ($response->successful()) {
            $responseData = $response->json();
            $user = $responseData['user'];

            // Parse created_at and last_login_at fields into DateTime objects
            $user['created_at'] = new DateTime($user['created_at']);
            $user['last_login_at'] = new DateTime($user['last_login_at']);

            return $user;
        } else {
            // Throw a PassageError or handle the failure as needed
            throw new PassageError('Failed to activate the user.');
        }
    }

    /**
     * Delete a user.
     *
     * @param string $userId The user ID.
     * @return bool True if the user deletion was successful; otherwise, false.
     * @throws PassageError
     */
    public function delete(string $userId): bool
    {
        // Construct the URL for the Passage API endpoint
        $url = 'https://api.passage.id/v1/apps/' . $this->appId . '/users/' . $userId;

        // Set the headers for the API request
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ];

        // Send the HTTP DELETE request to the Passage API
        $response = Http::withHeaders($headers)->delete($url);

        // Check if the request was successful
        if ($response->successful()) {
            return true;
        } else {
            // Throw a PassageError or handle the failure as needed
            throw new PassageError('Failed to delete the user.');
        }
    }

    /**
     * Create a new user.
     *
     * @param string|null $email The user's email address.
     * @param string|null $phone The user's phone number.
     * @return array The created user information.
     * @throws PassageError
     */
    public function create(?string $email = null, ?string $phone = null): array
    {
        // Validate that at least email or phone is provided
        if (empty($email) && empty($phone)) {
            throw new PassageError('Either email or phone must be provided.');
        }

        // Construct the URL for the Passage API endpoint
        $url = 'https://api.passage.id/v1/apps/' . $this->appId . '/users';

        // Set the headers for the API request
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ];

        // Create the request payload
        $payload = [
            'email' => $email,
            'phone' => $phone,
        ];

        // Send the HTTP POST request to the Passage API
        $response = Http::withHeaders($headers)->post($url, $payload);

        // Check if the request was successful
        if ($response->successful()) {
            $responseData = $response->json();
            $user = $responseData['user'];

            // Parse created_at and last_login_at fields into DateTime objects
            $user['created_at'] = new DateTime($user['created_at']);
            $user['last_login_at'] = new DateTime($user['last_login_at']);

            return $user;
        } else {
            // Throw a PassageError or handle the failure as needed
            throw new PassageError('Failed to create the user.');
        }
    }

    /**
     * Update a user's information.
     *
     * @param string $userId The user ID.
     * @param array $data The updated user data.
     * @return array The updated user information.
     * @throws PassageError
     */
    public function update(string $userId, array $data): array
    {
        // Construct the URL for the Passage API endpoint
        $url = 'https://api.passage.id/v1/apps/' . $this->appId . '/users/' . $userId;

        // Set the headers for the API request
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ];

        // Send the HTTP PATCH request to the Passage API
        $response = Http::withHeaders($headers)->patch($url, $data);

        // Check if the request was successful
        if ($response->successful()) {
            $responseData = $response->json();
            $user = $responseData['user'];

            // Parse created_at and last_login_at fields into DateTime objects
            $user['created_at'] = new DateTime($user['created_at']);
            $user['last_login_at'] = new DateTime($user['last_login_at']);

            return $user;
        } else {
            // Throw a PassageError or handle the failure as needed
            throw new PassageError('Failed to update the user.');
        }
    }
}
