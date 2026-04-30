<?php

return [
    'app_id' => env('DERIV_APP_ID'),
    'oauth_url' => 'https://oauth.deriv.com/oauth2/authorize',
    'redirect_uri' => env('DERIV_REDIRECT_URI', ''),
];
