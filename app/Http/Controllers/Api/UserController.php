<?php

namespace App\Http\Controllers\Api;

use App;
use App\Enums\Role;
use App\Enums\ScreenshotsState;
use App\Http\Requests\User\ListUsersRequest;
use App\Scopes\UserAccessScope;
use Settings;
use Carbon\Carbon;
use Exception;
use Filter;
use App\Mail\UserCreated;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use CatEvent;
use Mail;
use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\EditUserRequest;
use App\Http\Requests\User\SendInviteUserRequest;
use App\Http\Requests\User\ShowUserRequest;
use App\Http\Requests\User\DestroyUserRequest;
use App\Models\Setting;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class UserController extends ItemController
{
    protected const MODEL = User::class;

    /**
     * List
     *
     * Get list of Users with any params
     *
     * Required permission (any of): `users_list`, `users_full_access`
     */
    public function index(ListUsersRequest $request): JsonResponse
    {
        return $this->_index($request);
    }

    /**
     * Create
     *
     * Create User Entity
     *
     * Required permission (any of): `users_create`, `users_full_access`
     *
     * @param CreateUserRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function create(CreateUserRequest $request): JsonResponse
    {
        Filter::listen(Filter::getRequestFilterName(), static function ($requestData) use ($request) {
            $requestData['screenshots_state_locked'] = $request->user()->isAdmin() && ScreenshotsState::tryFrom($requestData['screenshots_state'])->mustBeInherited();

            return $requestData;
        });

        return $this->_create($request);
    }

    /**
     * Edit
     *
     * Edit User
     *
     * Required permission (any of): `users_edit`, `users_full_access`
     *
     * @param EditUserRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function edit(EditUserRequest $request): JsonResponse
    {
        Filter::listen(Filter::getActionFilterName(), static function (User $user) use ($request) {
            if ($user->screenshots_state_locked && !$request->user()->isAdmin()) {
                $user->screenshots_state = $user->getOriginal('screenshots_state');
                return $user;
            }

            // Lock by the user's own state, as in create(): the accessor applies global overrides and
            // per-viewer substitutions, and returns the raw request value only if it was just assigned.
            $state = ScreenshotsState::createFrom($user->getAttributes()['screenshots_state'] ?? null);
            $user->screenshots_state_locked = $request->user()->isAdmin() && $state?->mustBeInherited();

            return $user;
        });

        return $this->_edit($request);
    }

    /**
     * Show User
     *
     * Retrieves detailed information about a specific user.
     *
     * Required permission (any of): `users_show`, `users_full_access`
     *
     * @param ShowUserRequest $request
     * @return JsonResponse
     * @throws Exception
     * @throws Throwable
     */
    public function show(ShowUserRequest $request): JsonResponse
    {
        return $this->_show($request);
    }

    /**
     * Destroy
     *
     * Destroy User
     *
     * Required permission (any of): `users_remove`, `users_full_access`
     */
    public function destroy(DestroyUserRequest $request): JsonResponse
    {
        return $this->_destroy($request);
    }
    /**
     * Count
     *
     * Count Users
     *
     * Required permission (any of): `users_count`, `users_full_access`
     */
    public function count(ListUsersRequest $request): JsonResponse
    {
        return $this->_count($request);
    }

    /**
     * Send User Invitation
     *
     * Sends an invitation to a user by generating a password, marking the invitation as sent, and dispatching relevant events.
     *
     * Required permission: `users_invite`
     */
    public function sendInvite(SendInviteUserRequest $request): JsonResponse
    {
        $requestId = Filter::process(Filter::getRequestFilterName(), $request->validated('id'));

        $itemsQuery = $this->getQuery(['id' => $requestId]);

        CatEvent::dispatch(Filter::getBeforeActionEventName(), $requestId);

        $item = Filter::process(Filter::getActionFilterName(), $itemsQuery->first());

        $password = Str::random();
        $item->password = $password;
        $item->invitation_sent = true;
        $item->save();

        throw_unless($item, new NotFoundHttpException);

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$requestId, $item]);

        $language = Settings::scope('core')->get('language', 'en');

        Mail::to($item->email)->locale($language)->send(new UserCreated($item->email, $password));

        return responder()->success()->respond(204);
    }

    /**
     * Activity
     *
     * Updates the time of the user's last activity
     */
    public function updateActivity(): JsonResponse
    {
        $user = request()->user();

        CatEvent::dispatch(Filter::getBeforeActionEventName(), $user);

        Filter::process(Filter::getActionFilterName(), $user)->update(['last_activity' => Carbon::now()]);

        CatEvent::dispatch(Filter::getAfterActionEventName(), $user);

        return responder()->success()->respond(204);
    }
}
