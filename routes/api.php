<?php

use App\Http\Controllers\Api\AboutController;
use App\Http\Controllers\Api\CompanySettingsController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectMemberController;
use App\Http\Controllers\Api\Statistic\DashboardController;
use App\Http\Controllers\Api\Statistic\ProjectReportController;
use App\Http\Controllers\Api\Statistic\TimeUseReportController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TimeController;
use App\Http\Controllers\Api\TimeIntervalController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\Api\ScreenshotController;
use App\Http\Controllers\ScreenshotController as ScreenshotStaticController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\StatusController;
use Illuminate\Routing\Router;

// Static content processing
Route::group([
    'prefix' => 'uploads'
], static function (Router $router) {
    $router->get('screenshots/{screenshot}', [ScreenshotStaticController::class, 'screenshot']);
    $router->get('screenshots/thumbs/{screenshot}', [ScreenshotStaticController::class, 'thumbnail']);
});

// Routes for login/register processing
Route::group([
    'middleware' => 'throttle:120,1',
    'prefix' => 'auth',
], static function (Router $router) {
    $router->post('login', [AuthController::class, 'login']);
    $router->post('logout', [AuthController::class, 'logout']);
    $router->post('logout-from-all', [AuthController::class, 'logoutFromAll']);
    $router->post('refresh', [AuthController::class, 'refresh']);
    $router->get('me', [AuthController::class, 'me']);
    $router->post('password/reset/request', [PasswordResetController::class, 'request']);
    $router->post('password/reset/validate', [PasswordResetController::class, 'validate']);
    $router->post('password/reset/process', [PasswordResetController::class, 'process']);

    $router->get('register/{key}', [RegistrationController::class, 'getForm']);
    $router->post('register/{key}', [RegistrationController::class, 'postForm']);


    $router->get('desktop-key', [AuthController::class, 'issueDesktopKey']);
    $router->put('desktop-key', [AuthController::class, 'authDesktopKey']);
});

Route::get('status', [StatusController::class, '__invoke']);

// Main API routes
Route::group([
    'middleware' => ['auth:api', 'throttle:120,1'],
], static function (Router $router) {
    //Invitations routes
    $router->any('invitations/list', [InvitationController::class, 'index']);
    $router->get('invitations/count', [InvitationController::class, 'count']);
    $router->post('invitations/create', [InvitationController::class, 'create']);
    $router->post('invitations/resend', [InvitationController::class, 'resend']);
    $router->post('invitations/show', [InvitationController::class, 'show']);
    $router->post('invitations/remove', [InvitationController::class, 'destroy']);

    //Projects routes
    $router->any('projects/list', [ProjectController::class, 'index']);
    $router->any('projects/count', [ProjectController::class, 'count']);
    $router->post('projects/create', [ProjectController::class, 'create']);
    $router->post('projects/edit', [ProjectController::class, 'edit']);
    $router->any('projects/show', [ProjectController::class, 'show']);
    $router->post('projects/remove', [ProjectController::class, 'destroy']);

    $router->any('project-members/show', [ProjectMemberController::class, 'show']);
    $router->post('project-members/bulk-edit', [ProjectMemberController::class, 'bulkEdit']);

    //Tasks routes
    $router->any('tasks/list', [TaskController::class, 'index']);
    $router->any('tasks/count', [TaskController::class, 'count']);
    $router->post('tasks/create', [TaskController::class, 'create']);
    $router->post('tasks/edit', [TaskController::class, 'edit']);
    $router->any('tasks/show', [TaskController::class, 'show']);
    $router->post('tasks/remove', [TaskController::class, 'destroy']);

    //Users routes
    $router->any('users/list', [UserController::class, 'index']);
    $router->any('users/count', [UserController::class, 'count']);
    $router->post('users/create', [UserController::class, 'create']);
    $router->post('users/edit', [UserController::class, 'edit']);
    $router->any('users/show', [UserController::class, 'show']);
    $router->post('users/remove', [UserController::class, 'destroy']);
    $router->post('users/send-invite', [UserController::class, 'sendInvite']);

    //Screenshots routes
    $router->any('screenshots/list', [ScreenshotController::class, 'index']);
    $router->any('screenshots/count', [ScreenshotController::class, 'count']);
    $router->post('screenshots/create', [ScreenshotController::class, 'create']);
    $router->post('screenshots/edit', [ScreenshotController::class, 'edit']);
    $router->any('screenshots/show', [ScreenshotController::class, 'show']);
    $router->post('screenshots/remove', [ScreenshotController::class, 'destroy']);

    //Time Intervals routes
    $router->any('time-intervals/list', [TimeIntervalController::class, 'index']);
    $router->any('time-intervals/count', [TimeIntervalController::class, 'count']);
    $router->post('time-intervals/create', [TimeIntervalController::class, 'create']);
    $router->post('time-intervals/edit', [TimeIntervalController::class, 'edit']);
    $router->post('time-intervals/bulk-edit', [TimeIntervalController::class, 'bulkEdit']);
    $router->any('time-intervals/show', [TimeIntervalController::class, 'show']);
    $router->post('time-intervals/remove', [TimeIntervalController::class, 'destroy']);
    $router->post('time-intervals/bulk-remove', [TimeIntervalController::class, 'bulkDestroy']);

    $router->any('time-intervals/dashboard', [DashboardController::class, 'timeIntervals']);
    $router->any('time-intervals/day-duration', [DashboardController::class, 'timePerDay']);

    //Time routes
    $router->any('time/total', [TimeController::class , 'total']);
    $router->any('time/tasks', [TimeController::class, 'tasks']);

    //Role routes
    $router->any('roles/list', [RoleController::class, 'index']);
    $router->any('roles/count', [RoleController::class, 'count']);

    // Statistic routes
    $router->any('project-report/list', [ProjectReportController::class, 'report']);
    $router->any('project-report/list/tasks/{id}', [ProjectReportController::class, 'task']);
    $router->any('time-use-report/list', [TimeUseReportController::class, 'report']);

    // About
    $router->get('about', [AboutController::class, '__invoke']);
    $router->get('about/storage', [AboutController::class, 'storage']);
    $router->post('about/storage', [AboutController::class, 'startStorageClean']);

    //Company settings
    $router->get('company-settings', [CompanySettingsController::class, 'index']);
    $router->patch('company-settings', [CompanySettingsController::class, 'update']);
});

Route::any('(.*)', [Controller::class, 'universalRoute']);
