

********************************************************************************************************************
                                   [START DEBUG LOG: 2024-12-04 06:23:20 PM EST]                                    
********************************************************************************************************************



[DEBUG] Making WordPress API request to fetch shortcut
[SOURCE] edit-shortcut.js:168:17
{
    "url": "https://debotchery.ai/wp-admin/admin-ajax.php",
    "method": "POST",
    "data": "action=fetch_shortcut&security=b59d053ecf&post_id=64&source=WP"
}


[DEBUG] Received WordPress API response
[SOURCE] edit-shortcut.js:179:17
{
    "success": false,
    "data": {
        "success": false,
        "message": "Shortcut not found."
    }
}


[DEBUG] Fetching shortcut data from Switchblade API
[SOURCE] edit-shortcut.js:279:17
{
    "url": "https://debotchery-switchblade-bc6fa1ee4e01.herokuapp.com/shortcuts/64",
    "method": "GET",
    "headers": {
        "Content-Type": "application/json"
    }
}


[DEBUG] Error fetching shortcut data from Switchblade API
[SOURCE] edit-shortcut.js:300:17
{
    "status": 400,
    "statusText": "Bad Request",
    "error": "Bad Request"
}




********************************************************************************************************************
                                   [START DEBUG LOG: 2024-12-04 06:30:03 PM EST]                                    
********************************************************************************************************************



[DEBUG] Fetching shortcut data from Switchblade API
[SOURCE] edit-shortcut.js:279:17
{
    "url": "https://debotchery-switchblade-bc6fa1ee4e01.herokuapp.com/shortcuts/64",
    "method": "GET",
    "headers": {
        "Content-Type": "application/json"
    }
}


[DEBUG] Error fetching shortcut data from Switchblade API
[SOURCE] edit-shortcut.js:300:17
{
    "status": 400,
    "statusText": "Bad Request",
    "error": "Bad Request"
}


[DEBUG] Making WordPress API request to fetch shortcut
[SOURCE] edit-shortcut.js:168:17
{
    "url": "https://debotchery.ai/wp-admin/admin-ajax.php",
    "method": "POST",
    "data": "action=fetch_shortcut&security=b59d053ecf&post_id=64&source=WP"
}


[DEBUG] Received WordPress API response
[SOURCE] edit-shortcut.js:179:17
{
    "success": false,
    "data": {
        "success": false,
        "message": "Shortcut not found."
    }
}




********************************************************************************************************************
                                   [START DEBUG LOG: 2024-12-04 06:34:23 PM EST]                                    
********************************************************************************************************************



[DEBUG] Fetching shortcut data from Switchblade API
[SOURCE] https://debotchery.ai/wp-content/plugins/shortcuts-hub/assets/js/pages/edit-shortcut.js?ver=1733341734:279:17
{
    "url": "https://debotchery-switchblade-bc6fa1ee4e01.herokuapp.com/shortcuts/64",
    "method": "GET",
    "headers": {
        "Content-Type": "application/json"
    }
}


[DEBUG] Received WordPress API response
[SOURCE] https://debotchery.ai/wp-content/plugins/shortcuts-hub/assets/js/pages/edit-shortcut.js?ver=1733341734:179:17
{
    "success": false,
    "data": {
        "success": false,
        "message": "Shortcut not found."
    }
}


[DEBUG] Making WordPress API request to fetch shortcut
[SOURCE] https://debotchery.ai/wp-content/plugins/shortcuts-hub/assets/js/pages/edit-shortcut.js?ver=1733341734:168:17
{
    "url": "https://debotchery.ai/wp-admin/admin-ajax.php",
    "method": "POST",
    "data": "action=fetch_shortcut&security=b59d053ecf&post_id=64&source=WP"
}


[DEBUG] Error fetching shortcut data from Switchblade API
[SOURCE] https://debotchery.ai/wp-content/plugins/shortcuts-hub/assets/js/pages/edit-shortcut.js?ver=1733341734:300:17
{
    "status": 400,
    "statusText": "Bad Request",
    "error": "Bad Request"
}


