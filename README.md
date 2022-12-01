# WP Custom API
Simple app that allows you to use Wordpress as your authentication for external apps.

## Steps
* Install and Activate Plugin
* Navigate to Custom API in WP Admin
* Create an API Key for consumers of your api to use.

### Example cURL call
```
curl --location --request POST 'https://yourwpdomain.com/wp-json/api/v1/login' \
--header 'WPAUTHX: <your api key>' \
--header 'Content-Type: application/json' \
--data-raw '{
    "username": <Your WP username>,
    "password": <Your WP password>
}'
```