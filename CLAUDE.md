# Oauth2Connect

Notes for AI assistants working on the Dotclear plugin Oauth2Connect.

**Read first** the shared conventions of the author's Dotclear modules:
https://raw.githubusercontent.com/JcDenis/gh2da/master/CONVENTIONS.md
(if a web tool refuses the address, use `curl -s` on it from the shell).
The rules below take precedence for this module.

## Module

Allow third party connection on frontend.

Admin page. Public side: behaviors.

Required Dotclear and PHP versions: see `_define.php`.

## Structure

* `src/Backend.php`: admin side: menu entry, favorites, behaviors registration.
* `src/Frontend.php`: public side: behaviors, template tags and URL handlers registration.
* `src/Manage.php`: admin page of the module.
* `src/My.php`: module helpers (`My::id()`, settings, permissions).
* `src/OAuth2Client.php`: client helper.
* `src/OAuth2Store.php`: client store class.
* Requires FrontendSession (`requires` in `_define.php`).

## Pitfalls

* CI installs FrontendSession from the author's repositories, then from franck-paul's.

## Testing

* Check the admin page, in English and French.
* CI browses the common admin and public pages; extra pages can be listed in `.github/dotclear-test.txt`.
