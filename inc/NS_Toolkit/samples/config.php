<?php

$TMWNI_OPTIONS = TMWNI_Settings::getTabSettings();
ini_set('default_socket_timeout', 600);
define('NS_ENDPOINT', '2020_2');
define('NS_HOST', isset($TMWNI_OPTIONS['ns_host']) ? rtrim($TMWNI_OPTIONS['ns_host'], '/\\') : '');
define('NS_ACCOUNT', isset($TMWNI_OPTIONS['ns_account']) ? trim($TMWNI_OPTIONS['ns_account']) : '');
define('NS_APPID', isset($TMWNI_OPTIONS['ns_app_id']) ? trim($TMWNI_OPTIONS['ns_app_id']) : '');
define('NS_TOKEN_ID', isset($TMWNI_OPTIONS['ns_token_id']) ? trim($TMWNI_OPTIONS['ns_token_id']) : '');
define('NS_TOKEN_SECRET', isset($TMWNI_OPTIONS['ns_token_secret']) ? trim($TMWNI_OPTIONS['ns_token_secret']) : '');
define('NS_CONSUMER_KEY', isset($TMWNI_OPTIONS['ns_consumer_key']) ? trim($TMWNI_OPTIONS['ns_consumer_key']) : '');
define('NS_CONSUMER_SECRET', isset($TMWNI_OPTIONS['ns_consumer_secret']) ? trim($TMWNI_OPTIONS['ns_consumer_secret']) : '');

if (isset($TMWNI_OPTIONS['hma_algorithm_method'])) {
define('NS_HMAC_ALGORITHM', 'HMAC-SHA1' ==$TMWNI_OPTIONS['hma_algorithm_method'] ? 'HMAC-SHA1' : 'HMAC-SHA256');
define('NS_HMAC_METHOD', 'HMAC-SHA1' ==$TMWNI_OPTIONS['hma_algorithm_method'] ? 'sha1' : 'sha256');
} else {
define('NS_HMAC_ALGORITHM', '');
define('NS_HMAC_METHOD', '');	
}



// define('NS_ENDPOINT', '2020_2');
// define('NS_HOST', 'https://5635941.suitetalk.api.netsuite.com');
// define('NS_ACCOUNT', '5635941');
// define('NS_APPID', '');
// define('NS_TOKEN_ID', '33ce42db34129d01663a68fd6533b17dc37cee8b449d19a3ab0f5d745fcecbbf');
// define('NS_TOKEN_SECRET', 'e0c4a022e10c68ef496ccbbf474911a6a1f6b4b06ce766e49883d8248024a124');
// define('NS_CONSUMER_KEY','827a1ba879bc5fdb51b2f696a53aa7925dd8012fddd088f2e1d7f2fcd77e7df1');
// define('NS_CONSUMER_SECRET', '960debd07689c81243043167f9c0879ccc309816113720797fdb9324fe64727f');

// if (isset($TMWNI_OPTIONS['hma_algorithm_method'])) {
// define('NS_HMAC_ALGORITHM', 'HMAC-SHA1' ==$TMWNI_OPTIONS['hma_algorithm_method'] ? 'HMAC-SHA1' : 'HMAC-SHA256');
// define('NS_HMAC_METHOD', 'HMAC-SHA1' ==$TMWNI_OPTIONS['hma_algorithm_method'] ? 'sha1' : 'sha256');
// } else {
// define('NS_HMAC_ALGORITHM', '');
// define('NS_HMAC_METHOD', '');	
// }



