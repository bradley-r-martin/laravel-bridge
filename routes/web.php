<?php

Route::post('/bridge', 'LaravelBridge\Http\Controllers\Bridge\Bridge@respond')->middleware('web');
