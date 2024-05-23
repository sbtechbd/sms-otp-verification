<?php

// Enable auto-updates for this plugin



add_filter('plugins_api', 'custom_plugin_update', 20, 3);

function custom_plugin_update($result, $action, $args)
{
    if (!empty($args->slug) && $args->slug === 'twilio-sms-verification') {
        $json_url = 'https://example.com/path/to/plugin-update.json';
        $data = json_decode(file_get_contents($json_url), true);
        if ($data) {
            $result = $data;
        }
    }
    return $result;
}
