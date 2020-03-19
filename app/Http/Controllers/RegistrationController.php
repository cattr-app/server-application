<?php

namespace App\Http\Controllers;

use App\Mail\Registration as RegistrationMail;
use App\Models\Registration;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Webpatser\Uuid\Uuid;

/**
 * Class RegistrationController
 * @codeCoverageIgnore until it is implemented on frontend
*/
class RegistrationController extends Controller
{
    // One hour.
    protected const EXPIRATION_TIME = 60 * 60;

    /**
     * @noinspection MagicMethodsValidityInspection
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct()
    {
    }

    /**
     * @api             {post} /register/create Create
     * @apiDescription  Create unique register token and send email
     *
     * @apiVersion      1.0.0
     * @apiName         CreateRegistration
     * @apiGroup        Registration
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   register_create
     *
     * @apiParam {String}  email  New user email
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "email": "test@example.com"
     *  }
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {String}   key      Unique registration token
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "key": "..."
     *  }
     *
     * @apiErrorExample {json} No email
     *  HTTP/1.1 400 Bad Request
     *  {
     *    "success": false,
     *    "error": "Email is required"
     *  }
     *
     * @apiErrorExample {json} Email is busy
     *  HTTP/1.1 400 Bad Request
     *  {
     *    "success": false,
     *    "error": "User with this email is already exists"
     *  }
     *
     * @apiErrorExample {json} Message already sent
     *  HTTP/1.1 400 Bad Request
     *  {
     *    "success": false,
     *    "error": "E-Mail to this address is already sent"
     *  }
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     */
    /**
     * Creates a new registration token and sends an email to the specified address
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function create(Request $request): JsonResponse
    {
        $email = $request->input('email') ?: $request->input('emails');
        if (is_array($email)) {
            return $this->sendMassInvites($emails = $email);
        }

        if (!isset($email)) {
            return response()->json([
                'success' => false,
                'error' => 'Email is required',
            ], 400);
        }

        $user = User::where('email', $email)->first();
        if (isset($user)) {
            return response()->json([
                'success' => false,
                'error' => 'User with this email is already exists',
            ], 400);
        }

        $registration = Registration::where('email', $email)
            ->where('expires_at', '>=', time())
            ->first();
        if (isset($registration)) {
            return response()->json([
                'success' => false,
                'error' => 'E-Mail to this address is already sent',
            ], 400);
        }

        $registration = Registration::firstOrCreate([
            'email' => $email,
        ], [
            'key' => (string)Uuid::generate(),
            'expires_at' => time() + static::EXPIRATION_TIME,
        ]);

        Mail::to($email)->send(new RegistrationMail($registration->key));

        return response()->json([
            'success' => true,
            'key' => $registration->key,
        ]);
    }

    protected function sendMassInvites($emails)
    {
        $countEmails = count($emails);
        $users = User::whereIn('email', $emails)->get('email')->map(function ($el) {
            return $el->getAttribute('email');
        })->all();

        if ($countEmails === count($users)) {
            return response()->json([
                'success' => false,
                'error' => 'Users with this emails is already exists',
            ], 400);
        }

        $usersEmails = '';
        if (count($users) > 0) {
            $usersEmails = implode(', ', $users);
        }

        if ($usersEmails) {
            return response()->json([
                'success' => false,
                'error' => "These emails: {$usersEmails} is already exists",
            ], 400);
        }

        $registrations = Registration::whereIn('email', $emails)
            ->where('expires_at', '>=', time())
            ->get(['email', 'key'])->map(function ($el) {
                return $el->getAttribute('email');
            })->all();

        if (count($registrations) > 0) {
            $registrationsEmails = implode(', ', $registrations);
        }
        if (isset($registrationsEmails)) {
            return response()->json([
                'success' => false,
                'error' => "{$registrationsEmails} to these addresses is already sent",
            ], 400);
        }

        $response = [];

        foreach ($emails as $email) {
            $registration = Registration::firstOrCreate([
                'email' => $email,
            ], [
                'key' => (string)Uuid::generate(),
                'expires_at' => time() + static::EXPIRATION_TIME,
            ]);

            Mail::to($email)->send(new RegistrationMail($registration->key));

            $response['users'][] = ['email' => $email, 'key' => $registration->key];
        }

        return response()->json(['success' => true, $response]);
    }

    /**
     * @api             {get} /auth/register/{key} Get
     * @apiDescription  Returns registration form data by a registration token
     *
     * @apiVersion      1.0.0
     * @apiName         GetRegistration
     * @apiGroup        Registration
     *
     * @apiParam (Parameters from url) {String}  key  User registration key
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {String}   email    Registration e-mail
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "email": "test@example.com"
     *  }
     *
     * @apiErrorExample {json} Email not found
     *  HTTP/1.1 404 Not found
     *  {
     *    "success": false,
     *    "error": "Not found"
     *  }
     *
     * @apiUse          400Error
     */
    /**
     * Returns a data for the registration form by a registration token
     * @param $key
     * @return JsonResponse
     */
    public function getForm($key): JsonResponse
    {
        $registration = Registration::where('key', $key)
            ->where('expires_at', '>=', time())
            ->first();
        if (!isset($registration)) {
            return response()->json([
                'success' => false,
                'error' => 'Not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'email' => $registration->email,
        ]);
    }

    /**
     * Creates a new user.
     *
     * @api             {post} /auth/register/{key} Post
     * @apiDescription  Registers user by key
     *
     * @apiVersion      1.0.0
     * @apiName         PostRegistration
     * @apiGroup        Registration
     *
     * @apiParam (Parameters from url) {String}  key  User registration key
     *
     * @apiParam {String}  email     New user email
     * @apiParam {String}  password  New user password
     * @apiParam {String}  fullName  New user name
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "email": "johndoe@example.com",
     *    "password": "amazingpassword",
     *    "fullName": "John Doe"
     *  }
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {Number}   user_id  New user ID
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "user_id": 2
     *  }
     *
     * @apiErrorExample {json} Email not found
     *  HTTP/1.1 404 Not found
     *  {
     *    "success": false,
     *    "error": "Not found"
     *  }
     *
     * @apiErrorExample {json} Email mismatch
     *  HTTP/1.1 400 Bad request
     *  {
     *    "success": false,
     *    "error": "Email mismatch"
     *  }
     *
     * @apiUse          400Error
     */
    /**
     * @param Request $request
     * @param $key
     * @return JsonResponse
     * @throws Exception
     */
    public function postForm(Request $request, string $key): JsonResponse
    {
        $registration = Registration::where('key', $key)
            ->where('expires_at', '>=', time())
            ->first();

        if (!isset($registration)) {
            return response()->json([
                'success' => false,
                'error' => 'Not found',
            ], 404);
        }

        if ($request->input('email') !== $registration->email) {
            return response()->json([
                'success' => false,
                'error' => 'Email mismatch',
            ], 400);
        }

        /** @var User $user */
        $user = User::create([
            'full_name' => $request->input('full_name'),
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
            'active' => true,
            'manual_time' => false,
            'screenshots_active' => true,
            'computer_time_popup' => 3,
            'screenshots_interval' => 10,
            'role_id' => 2,
        ]);

        $registration->delete();

        return response()->json([
            'success' => true,
            'user_id' => $user->id,
        ]);
    }
}
