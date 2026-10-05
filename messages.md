# githappens message contract

Requests are PHP arrays sent with RabbitMQClient > send_request()
Every request has a lowercase 'type'
Every response has a 'success' (boolean) and 'message' (string)
DO NOT send password hashes back to the frontend 

## register
Request: type, username, password
Response: success, message

## login
Request: type, username, password
Response: success, message, session_id (on success)

## validate_session
Request: type, session_id
Response: success, message, username (on success)

## logout
Request: type, session_id
Response: success, message

## error/missing type
Response: success = false, message = "unknown request type"
