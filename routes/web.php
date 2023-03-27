<?php
use App\Http\Controllers\Controller;

Route::fallback([Controller::class, 'frontendRoute']);
