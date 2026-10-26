# githappens Message Contract

## Rules
- Requests are PHP arrays sent with rabbitMQClient->send_request().
- Every request has a `type` (snake_case).
- Field keys start lower case and every new word gets capitalized (sessionId, userId).
- Every response has `success` (bool) and `message` (string).
- Failed responses also include `code` (int) from the table below.
- Never send passwords or password hashes back to the frontend.

## Routes
- databaseServer: databaseExchange > databaseQueue answered by rabbitMQServer.php
    Types: register, login, validate_session, logout, list_posts, get_post, create_post, create_reply
- dmzServer: dmzExchange > dmzQueue answered by dmzServer.php
    Types: ping, get_card_prices, card_search

## Error codes
- 1: missing or unknown request type 
- 2: missing required field 
- 3: invalid username or password 
- 4: username or email already taken 
- 5: invalid or expired session 
- 6: post not found
- 9: server or database error
 
## register
Request:  type, username, email, password
Success:  success, message
Failure codes: 2, 4, 9

## login
Request:  type, username, password
Success:  success, message, sessionId, user (id, username, email)
Failure codes: 2, 3, 9

## validate_session
Request:  type, sessionId
Success:  success, message, username
Failure codes: 2, 5, 9

## logout
Request:  type, sessionId
Success:  success, message
Failure codes: 2, 5, 9

## list_posts
Request:  type, sessionId
Success:  success, message, posts
Failure codes: 5, 9

## get_post
Request:  type, sessionId, postId
Success:  success, message, post, replies
Failure codes: 5, 6, 9

## create_post
Request:  type, sessionId, title, body
Success:  success, message, postId
Failure codes: 2, 5, 9

## create_reply
Request:  type, sessionId, postId, body
Success:  success, message
Failure codes: 2, 5, 6, 9

## ping (dmzServer)
Request: type
Success: success, message 
Failure codes: 1

## card_search (dmzServer)
Request: type, cardName
Success: success, message, data (matching cards and id's)
Failure codes: 2, 9

## get_card_prices (dmzServer)
Request: type, cardId (TCG API ID)
Success: success, message, data
Failure codes: 2, 9