However, since you are using Inertia, you have a unique advantage here. You can actually leverage what you just installed to solve your external iframe problem without needing database tokens at all.

How to use your new API setup with Inertia (Stateful API)
Since your external iframe (exam-runner.my-example.com) needs to communicate with your backend, you can use Sanctum's Cookie-Based SPA Authentication instead of Bearer Tokens.

Because you installed the API features, Sanctum's middleware is already registered. You can configure your /api/me and /api/save-exam routes to authenticate against the user's active web session cookie instead of looking for a token string.

Here is how your routes/api.php file can look to support your Svelte/Inertia setup securely:

PHP
<?php

use App\Http\Controllers\API\VerifyRunnerSessionController;
use App\Http\Controllers\API\SaveExamController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Stateful / Cookie Protection)
|--------------------------------------------------------------------------
| By using 'auth:sanctum', Sanctum will automatically inspect the incoming 
| web session cookie sent by your browser/iframe context.
*/
Route::middleware('auth:sanctum')->group(function () {
    
    // GET /api/me?exam_id=123
    Route::get('/me', VerifyRunnerSessionController::class);
    
    // POST /api/save-exam
    Route::post('/save-exam', SaveExamController::class);
    
});
The Secret Sauce for your Iframe: Cross-Origin Cookies
Because your iframe is on a separate domain, browsers will strip your main app's session cookie by default unless you tell Laravel to explicitly allow it.

To make this stateful API setup work seamlessly with Inertia and your Svelte iframe, update two files in your project:

1. In your .env File
Tell Sanctum which domains are allowed to authenticate using stateful web sessions, and ensure cookies are configured to pass through cross-origin contexts:

Code snippet
SANCTUM_STATEFUL_DOMAINS=your-main-app.com,exam-runner.my-example.com
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=none
(Note: SESSION_SAME_SITE=none tells modern browsers that it is safe to send the authentication cookie inside an cross-domain iframe).

2. In your config/cors.php
Ensure that the supports_credentials flag is set to true so cookies are allowed to travel over your API calls:

PHP
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_methods' => ['*'],
'allowed_origins' => ['https://exam-runner.my-example.com'],
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => true, // <-- CRITICAL: Allows cookies over CORS
Summary