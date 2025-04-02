===Installation===
1. Run composer install
2. call vapid.php and copy public and private key values in keys/public_key.txt and keys/private_key.txt
3. dump push_notifications.sql into a database and save credentials in keys/db_credentials.txt in the format
- user: yourdbuser
- db: yourdbname
- password: yourpassword
- host: yourhost
4. Save a random string in a File keys/http_auth_bearer.txt and share it only with the Client Devs.