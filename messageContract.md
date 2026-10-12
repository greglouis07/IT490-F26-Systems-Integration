# githappens Message Contract

## Rules
- Requests are PHP arrays sent with rabbitMQClient->send_request().
- Every request has a `type` (snake_case).
- Field keys start lower case and every new word gets capitalized (sessionId, userId).
- Every response has `success` (bool) and `message` (string).
- Failed responses also include `code` (int) from the table below.
- Never send passwords or password hashes back to the frontend.

## Error codes
- 1: missing or unknown request type 
- 2: missing required field 
- 3: invalid username or password 
- 4: username already taken 
- 5: invalid or expired session 
- 9: server or database error
 
## register
Request:  type, username, password
Success:  success, message
Failure codes: 2, 4, 9

## login
Request:  type, username, password
Success:  success, message, sessionId
Failure codes: 2, 3, 9

## validate_session
Request:  type, sessionId
Success:  success, message, username
Failure codes: 2, 5, 9

## logout
Request:  type, sessionId
Success:  success, message
Failure codes: 2, 5, 9
