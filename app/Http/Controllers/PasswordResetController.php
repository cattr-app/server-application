<?php

namespace App\Http\Controllers;

use App\Exceptions\Entities\AuthorizationException;
use App\Helpers\Recaptcha;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use DB;
use Mail;
use Password;
use Validator;

class PasswordResetController extends BaseController
{
    public function __construct(private Recaptcha $recaptcha)
    {
    }

    /**
     * Validate
     *
     * Validates password reset token
     *
     * @param Request $request
     * @return JsonResponse
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

        return new JsonResponse(['message' => 'Password reset data is valid']);
    }

    /**
     * Request
     *
     * Sends email to user with reset link
     *
     * @param Request $request
     * @return JsonResponse
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

        Password::broker()->sendResetLink(['email' => $credentials['email']]);

        return new JsonResponse([
            'message' => 'Link for restore password has been sent to specified email',
        ]);
    }


    /**
     * Process
     *
     * Resets user password
     *
     * @param Request $request
     * @return JsonResponse
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

        $resetUser = null;
        $response = Password::broker()->reset(
            $request->all(),
            static function (User $user, string $password) use (&$resetUser) {
                $user->password = $password;
                $user->save();
                event(new PasswordResetEvent($user));
                $resetUser = $user;
            }
        );

        if ($response !== Password::PASSWORD_RESET) {
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_UNAUTHORIZED);
        }

        $token = $resetUser->createToken('password-reset')->plainTextToken;

        return new JsonResponse([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => null,
            'user' => $resetUser,
        ]);
    }
}
