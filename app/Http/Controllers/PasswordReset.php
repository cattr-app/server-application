<?php

namespace App\Http\Controllers;

use App\Exceptions\Entities\AuthorizationException;
use App\Helpers\RecaptchaHelper;
use App\User;
use Hash;
use Illuminate\Auth\Events\PasswordReset as PasswordResetAliasEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Password;
use DB;
use Illuminate\Http\Request;
use Validator;

class PasswordReset extends BaseController
{
    /**
     * @var RecaptchaHelper
     */
    private $recaptcha;

    /**
     * @param RecaptchaHelper $recaptcha
     * @api {post} /api/auth/password/reset/request Send reset e-mail
     * @apiDescription Get user JWT
     *
     *
     * @apiVersion 0.1.0
     * @apiName Send reset
     * @apiGroup Auth
     *
     * @apiParam {String}   login       User login
     * @apiParam {String}   recaptcha   Recaptcha token
     *
     * @apiError (Error 401) {String} Error Error
     *
     * @apiParamExample {json} Request Example
     *  {
     *      "login":      "johndoe@example.com",
     *      "recaptcha":  "03AOLTBLR5UtIoenazYWjaZ4AFZiv1OWegWV..."
     *  }
     *
     * @apiUse AuthAnswer
     * @apiUse UnauthorizedError
     */
    public function __construct(RecaptchaHelper $recaptcha)
    {
        $this->recaptcha = $recaptcha;
    }

    /*TODO API DOCS */
    public function validate(Request $request)
    {
        $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'token' => 'required|string']
        );
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'asd'], 400);
        }

        $resetRequest = DB::table('password_resets')
            ->where('email', $request->input('email'))->first();

        if (!$resetRequest || (time() - strtotime($resetRequest->created_at) > 600)) {
            return response()->json(['success' => false, 'message' => 'Invalid password reset data'], 401);
        }

        return response()->json(['success' => true, 'message' => 'Password reset data is valid']);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function request(Request $request)
    {
        $validator = Validator::make($request->all(), ['email' => 'required|email',]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'asd'], 400);
        }

        $credentials = $request->only(['email', 'recaptcha']);
        $this->recaptcha->check($credentials);
        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            $this->recaptcha->incrementCaptchaAmounts();
            $this->recaptcha->check($credentials);

            return response()->json([
                'success' => false,
                'message' => 'User with such email isn’t found',
            ], 404);
        }

        $this->recaptcha->clearCaptchaAmounts();

        Password::broker()->sendResetLink($credentials);

        return response()->json([
            'success' => true,
            'message' => 'Link for restore password has been sent to specified email.',
        ], 200);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @api {post} /api/auth/password/reset/process Reset
     * @apiDescription Get user JWT
     *
     *
     * @apiVersion 0.1.0
     * @apiName Reset
     * @apiGroup Auth
     *
     * @apiParam {String}   email       User email
     * @apiParam {String}   token       Password reset token
     * @apiParam {String}   password    New password
     * @apiParam {String}   password_confirmation   Password confirmation
     * @apiParam {String}   recaptcha   Recaptcha token
     *
     * @apiSuccess {String}     access_token  Token
     * @apiSuccess {String}     token_type    Token Type
     * @apiSuccess {String}     expires_in    Token TTL in seconds
     * @apiSuccess {Array}      user          User Entity
     *
     * @apiError (Error 401) {String} Error Error
     *
     * @apiParamExample {json} Request Example
     *  {
     *      "email":      "johndoe@example.com",
     *      "token":      "16184cf3b2510464a53c0e573c75740540fe...",
     *      "password":   "amazingpassword",
     *      "password_confirmation":   "amazingpassword",
     *      "recaptcha":  "03AOLTBLR5UtIoenazYWjaZ4AFZiv1OWegWV..."
     *  }
     *
     * @apiUse AuthAnswer
     * @apiUse UnauthorizedError
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
            return response()->json(['success' => false, 'message' => 'Bad request'], 400);
        }

        $resetRequest = DB::table('password_resets')
            ->where('email', $request->input('email'))->first();

        if (!$resetRequest || (time() - strtotime($resetRequest->created_at) > 600)) {
            return response()->json([
                    'success' => false,
                    'message' => 'Password reset request with specified data not exists or already expired']
                , 404);
        }

        $response = Password::broker()->reset($request->all(),
            function (User $user, string $password) {
                $user->password = Hash::make($password);
                $user->save();
                event(new PasswordResetAliasEvent($user));
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

        return response()->json([
            'access_token' => $token->token,
            'token_type' => 'bearer',
            'expires_in' => $token->expires_at,
            'user' => $user
        ]);
    }
}
