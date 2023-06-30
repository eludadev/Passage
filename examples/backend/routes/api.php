<?php

use Eludadev\Passage\Errors\PassageError;
use Eludadev\Passage\Passage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// This route handles the authentication process for the '/auth' endpoint

Route::post('/auth', function (Request $request) {
    try {
        // Create a new instance of the Passage class using the Passage API credentials from the environment variables
        $passage = new Passage(env('PASSAGE_APP_ID'), env('PASSAGE_API_KEY'), 'HEADER');

        // Authenticate the request using the Passage API
        $userId = $passage->authenticateRequest($request);

        if ($userId) {
            // If authentication is successful, retrieve user data using the Passage API
            $userData = $passage->user->get($userId);

            // Determine the identifier based on the user data (email or phone)
            $identifier = $userData['email'] ? $userData['email'] : $userData['phone'];

            // Return the authentication status and identifier
            return [
                'authStatus' => 'success',
                'identifier' => $identifier
            ];
        }
    } catch (PassageError $e) {
        // Catch any errors that occur during the authentication process and echo the error message
        echo $e->getMessage();

        // Return the authentication failure status
        return [
            'authStatus' => 'failure'
        ];
    }
});
