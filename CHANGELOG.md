### Unreleased

* [Feature] Add new request, interactor etc for a user to change their email address with an
  email verification step before the change is persisted. 
* [BREAKING] Refactor responsibility for generating parameters for email verification links : 
  now produced by the EmailVerificationRequest to allow for simpler addition of new kinds of 
  links / tokens.
* [BREAKING] Add leaky-bucket based rate limiting of email verifications sent to users:
  * Adds new constructor dependency to EmailVerificationInteractor
  * Adds new possible EmailVerificationResponse with status ERROR_RATE_LIMITED - which means
    an email would have been sent but wasn't because that user has already been sent one/some of 
    that type.
  * Adds new possible LoginReponse with status ERROR_PASSWORD_INCORRECT_RESET_THROTTLED - which
    means they got their password wrong and we would have sent an email but we've already recently
    sent some. End-user applications may want to show this separately, or treat it the same as an
    ERROR_PASSWORD_INCORRECT to handle it silently.
* [Feature]  Automatically update user password hash on login if hash configuration has changed
* [internal] Extract SaveSpyingUserRepository mock for reuse
* [BREAKING] Rename UserNotificationMailer::send to ::sendWardenNotification
* [BREAKING] Rename UserRepository methods to ingenerator conventions
* [BREAKING] UserRepository now throws if loading user with an unknown ID

### v0.2.1 (2018-09-26)

* Support email verification request for when a new user is invited e.g. server-side by an admin. Allow the code to 
  follow the password-reset flow but specifiy a different email template for the message.

### v0.2.0 (2018-03-13)

* Improve / extend the UrlProvider interface to cover customisation of more URLs and support single-action controllers.
  Also means that all expected / generated URLs have changed so this is breaking for any links that have been produced
  which will all now be invalid.

### v0.1.0 (2018-02-13)

* First version, extracted from host project
