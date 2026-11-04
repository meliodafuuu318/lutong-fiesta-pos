<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Every signed in user listens to "something changed" pings; they carry ids only, never order details.
Broadcast::channel('pos', fn ($user) => $user !== null);
