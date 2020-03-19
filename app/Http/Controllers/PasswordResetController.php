<?php

namespace App\Http\Controllers;

use App\Exceptions\Entities\AuthorizationException;
use App\Helpers\RecaptchaHelper;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class PasswordResetController extends BaseController
{
    /**
     * @var RecaptchaHelper
     */
    private $recaptcha;

    public function __construct(RecaptchaHelper $recaptcha)
    {
        $this->recaptcha = $recaptcha;
    }

    /**
     * @api             {get} /v1/auth/password/reset/validate Validate
     * @apiDescription  Validates password reset token
     *
     * @apiVersion      1.0.0
     * @apiName         Validate token
     * @apiGroup        Password Reset
     *
     * @apiParam {String}  email  User email
     * @apiParam {String}  token  Password reset token
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "email": "johndoe@example.com",
     *    "token": "03AOLTBLR5UtIoenazYWjaZ4AFZiv1OWegWV..."
     *  }
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {String}   message  Message from server
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "message": "Password reset data is valid"
     *  }
     *
     * @apiUse         400Error
     * @apiUse         ParamsValidationError
     * @apiUse         InvalidPasswordResetDataError
     */
    /**
     * @throws AuthorizationException
     */
    public function validate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|string'
        ]);

        if ($validator->fails()) {
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_VALIDATION_FAILED);
        }

        $user = Password::broker()->getUser($request->all());
        if (!$user) {
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_INVALID_PASSWORD_RESET_DATA);
        }

        $isValidToken = Password::broker()->getRepository()->exists($user, $request->input('token'));
        if (!$isValidToken) {
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_INVALID_PASSWORD_RESET_DATA);
        }

        return new JsonResponse(['success' => true, 'message' => 'Password reset data is valid']);
    }

    /**
     * @api             {post} /v1/auth/password/reset/request Request
     * @apiDescription  Sends email to user with reset link
     *
     * @apiVersion      1.0.0
     * @apiName         Request
     * @apiGroup        Password Reset
     *
     * @apiParam {String}  login         User login
     * @apiParam {String}  [recaptcha]   Recaptcha token
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "email": "johndoe@example.com",
     *    "recaptcha": "03AOLTBLR5UtIoenazYWjaZ4AFZiv1OWegWV..."
     *  }
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {String}   message  Message from server
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "message": "Link for restore password has been sent to specified email"
     *  }
     *
     * @apiUse         400Error
     * @apiUse         ParamsValidationError
     * @apiUse         NoSuchUserError
     * @apiUse         CaptchaError
     * @apiUse         LimiterError
     */
    /**
     * @throws AuthorizationException
     */
    public function request(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), ['email' => 'required|email']);

        if ($validator->fails()) {
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_VALIDATION_FAILED);
        }

        $credentials = $request->only(['email', 'recaptcha']);
        $this->recaptcha->check($credentials);
        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            $this->recaptcha->incrementCaptchaAmounts();
            $this->recaptcha->check($credentials);

            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_USER_NOT_FOUND);
        }

        $this->recaptcha->clearCaptchaAmounts();

        Password::broker()->sendResetLink($credentials);

        return new JsonResponse([
            'success' => true,
            'message' => 'Link for restore password has been sent to specified email',
        ]);
    }


    /**
     * @api             {post} /v1/auth/password/reset/process Process
     * @apiDescription  Resets user password
     *
     * @apiVersion      1.0.0
     * @apiName         Process
     * @apiGroup        Password Reset
     *
     * @apiParam {String}  email                  User email
     * @apiParam {String}  token                  Password reset token
     * @apiParam {String}  password               New password
     * @apiParam {String}  password_confirmation  Password confirmation
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "email": "johndoe@example.com",
     *    "token": "16184cf3b2510464a53c0e573c75740540fe...",
     *    "password_confirmation": "amazingpassword",
     *    "password": "amazingpassword"
     *  }
     *
     * @apiSuccess {Boolean}  success       Indicates successful request when `TRUE`
     * @apiSuccess {String}   access_token  Token
     * @apiSuccess {String}   token_type    Token Type
     * @apiSuccess {String}   expires_in    Token TTL in seconds
     * @apiSuccess {Object}   user          User Entity
     *
     * @apiUse         UserObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "access_token": "16184cf3b2510464a53c0e573c75740540fe...",
     *    "token_type": "bearer",
     *    "password": "amazingpassword",
     *    "expires_in": "3600",
     *    "user": {}
     *  }
     *
     * @apiUse         400Error
     * @apiUse         ParamsValidationError
     * @apiUse         InvalidPasswordResetDataError
     * @apiUse         UnauthorizedError
     */
    /**
     * @throws AuthorizationException
     */
    public function process(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required',
            'password_confirmation' => 'required'
        ]);
        if ($validator->fails()) {
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_VALIDATION_FAILED);
        }

        $resetRequest = DB::table('password_resets')
            ->where('email', $request->input('email'))
            ->first();

        if (!$resetRequest) {
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_INVALID_PASSWORD_RESET_DATA);
        }

        $response = Password::broker()->reset(
            $request->all(),
            static function (User $user, string $password) {
                $user->password = Hash::make($password);
                $user->save();
                event(new PasswordResetEvent($user));
                auth()->login($user);
            }
        );

        if ($response !== Password::PASSWORD_RESET) {
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_UNAUTHORIZED);
        }

        $tokenString = auth()->refresh();
        /** @var User $user */
        $user = auth()->user();

        $token = $user->addToken($tokenString);

        return new JsonResponse([
            'success' => true,
            'access_token' => $token->token,
            'token_type' => 'bearer',
            'expires_in' => $token->expires_at,
            'user' => $user
        ]);
    }
}
