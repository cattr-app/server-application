<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Invitation\CreateInvitationRequest;
use App\Http\Requests\Invitation\ListInvitationRequest;
use App\Http\Requests\Invitation\DestroyInvitationRequest;
use App\Http\Requests\Invitation\ShowInvitationRequest;
use App\Http\Requests\Invitation\UpdateInvitationRequest;
use App\Models\Invitation;
use App\Services\InvitationService;
use Exception;
use Filter;
use Illuminate\Http\JsonResponse;
use Throwable;

class InvitationController extends ItemController
{
    protected const MODEL = Invitation::class;

    /**
     * Show
     *
     * Show invitation.
     */
    public function show(ShowInvitationRequest $request): JsonResponse
    {
        return $this->_show($request);
    }

    /**
     * List
     *
     * Get list of invitations.
     */
    public function index(ListInvitationRequest $request): JsonResponse
    {
        return $this->_index($request);
    }

    /**
     * Create
     *
     * Creates a unique invitation token and sends an email to the users
     */
    public function create(CreateInvitationRequest $request): JsonResponse
    {
        $requestData = Filter::process(Filter::getRequestFilterName(), $request->validated());

        $invitations = [];

        foreach ($requestData['users'] as $user) {
            $invitations[] = InvitationService::create($user);
        }

        return responder()->success($invitations)->respond();
    }

    /**
     * Resend
     *
     * Updates the token expiration date and sends an email to the user's email address.
     */
    public function resend(UpdateInvitationRequest $request): JsonResponse
    {
        $requestData = Filter::process(Filter::getRequestFilterName(), $request->validated());

        $invitation = InvitationService::update($requestData['id']);

        return responder()->success($invitation)->respond();
    }

    /**
     * Destroy
     *
     * Destroy User
     */
    public function destroy(DestroyInvitationRequest $request): JsonResponse
    {
        return $this->_destroy($request);
    }

    /**
     * Count Invitations
     *
     * Returns the total count of invitations
     */
    public function count(ListInvitationRequest $request): JsonResponse
    {
        return $this->_count($request);
    }
}
