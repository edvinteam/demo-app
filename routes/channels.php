<?php

use Illuminate\Support\Facades\Broadcast;

// The tasks channel is public -- no auth needed.
// All visitors can see real-time task updates.
Broadcast::channel('tasks', function () {
    return true;
});
