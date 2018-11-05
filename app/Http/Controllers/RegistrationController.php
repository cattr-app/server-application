<?php

namespace App\Http\Controllers;

use App\Mail\Registration as RegistrationMail;
use App\Models\Registration;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Webpatser\Uuid\Uuid;

class RegistrationController extends Controller
{
    // One hour.
    const EXPIRATION_TIME = 60 * 60;

    /**
     * Creates a new registration token and sends an email to the specified address.
     *
     * @api {post} /api/v1/register/create Create
     * @apiName CreateRegistration
     * @apiGroup Registration
     * @apiDescription Create unique register token and send email
     * @apiVersion 0.1.0
     *
     * @apiParam {String} email E-Mail
     *
     * @apiParamExample {json} Request Example
     * {
     *   "email": "test@example.com"
     * }
     *
     * @apiSuccess {String} key Unique registration token
     *
     * @apiSuccessExample {json} Response Example
     * {
     *   "key": "..."
     * }
     */
    public function create(Request $request): JsonResponse
    {
        $email = $request->json()->get('email');
        if (!isset($email)) {
            return response()->json([
                'error' => 'Email is required',
            ], 400);
        }

        $user = User::where('email', $email)->first();
        if (isset($user)) {
            return response()->json([
                'error' => 'User with this email is already exists',
            ], 400);
        }

        $registration = Registration::where('email', $email)
            ->where('expires_at', '>=', time())
            ->first();
        if (isset($registration)) {
            return response()->json([
                'error' => 'E-Mail to this address is already sent',
            ], 400);
        }

        $registration = Registration::firstOrCreate([
            'email' => $email,
        ], [
            'key' => (string) Uuid::generate(),
            'expires_at' => time() + static::EXPIRATION_TIME,
        ]);

        Mail::to($email)->send(new RegistrationMail($registration->key));

        return response()->json([
            'key' => $registration->key,
        ]);
    }

    /**
     * Returns a data for the registration form by a registration token.
     *
     * @api {get} /api/auth/register/{key} Get
     * @apiName GetRegistration
     * @apiGroup Registration
     * @apiDescription Returns registration form data by a registration token
     * @apiVersion 0.1.0
     *
     * @apiSuccess {String} email Registration e-mail
     *
     * @apiSuccessExample {json} Response Example
     * {
     *   "email": "test@example.com"
     * }
     */
    public function getForm($key): JsonResponse
    {
        $registration = Registration::where('key', $key)
            ->where('expires_at', '>=', time())
            ->first();
        if (!isset($registration)) {
            return response()->json([
                'error' => 'Not found',
            ], 404);
        }

        return response()->json([
            'email' => $registration->email,
        ]);
    }

    /**
     * Creates a new user.
     */
    public function postForm(Request $request, $key): JsonResponse
    {
        $registration = Registration::where('key', $key)
            ->where('expires_at', '>=', time())
            ->first();
        if (!isset($registration)) {
            return response()->json([
                'error' => 'Not found',
            ], 404);
        }

        /** @todo: create user */

        $registration->delete();

        return response()->json([
            'user_id' => 0,
        ]);
    }
}
