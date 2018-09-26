### Unreleased

### v0.2.1 (2018-09-26)

* Support email verification request for when a new user is invited e.g. server-side by an admin. Allow the code to 
  follow the password-reset flow but specifiy a different email template for the message.

### v0.2.0 (2018-03-13)

* Improve / extend the UrlProvider interface to cover customisation of more URLs and support single-action controllers.
  Also means that all expected / generated URLs have changed so this is breaking for any links that have been produced
  which will all now be invalid.

### v0.1.0 (2018-02-13)

* First version, extracted from host project
