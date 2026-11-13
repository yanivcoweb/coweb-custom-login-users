# Coweb Custom Login Users

A WordPress plugin that adds front-end pages for login, registration, password reset and account details, with an admin approval step for new users.

This is the Coweb line of the plugin, from version 2.0, in the folder `coweb-custom-login-users`. It grew out of Custom Login Users 1.9, which other sites keep running as it was. The two are not to be active on one site together: they share their function names and settings.

**Author:** Yaniv Sasson
**Version:** 2.0.0

---

## Features

- Front-end pages for Login, Register, Lost Password, Set Password and My Account, all submitted over AJAX
- User approval workflow (Pending → Approved or Rejected), kept as a status on the user, with links and bulk actions on the Users list. The status stops nobody from logging in and hides no content: a site that wants that does it in its theme
- Flexible register form: turn fields on and off, make them required or optional, change their order, add your own, and choose which ones users can change on My Account
- Admin notification email on new registrations with what the user filled in, an email with a set-password link upon approval, and a rejection email
- Optional emails to new users: a link that confirms their address before the registration reaches the admin, and a note that the registration was received
- Every label and message can be edited in the settings, so the forms work in any language, including right-to-left
- Hebrew built in: on a site in Hebrew, the forms, messages and emails come in Hebrew without typing them. Other languages can be added with a translation file
- Optional automatic approval, for sites that want sign-ups without waiting for an admin
- A theme can replace the HTML of any form with its own copy
- Configurable password rules (min length, uppercase, lowercase, numbers, symbols)
- No roles of its own: a new user gets the default role of the site
- Protection against spam and abuse: a hidden bot trap and a limit on the registration form, a limit on reset emails, and the same answer for known and unknown accounts on the login and lost-password forms
- Optional login protection: a limit on failed logins across the whole site, and Cloudflare Turnstile or Google reCAPTCHA on the forms
- Changing the email address on My Account needs the current password

The plugin adds pages next to the default ones, and `wp-login.php` keeps working. To send `wp-login.php` and the links to it to your pages instead, turn on **Replace wp-login.php**.

---

## Shortcodes

| Shortcode | Older name, still supported | Description |
|---|---|---|
| `[clu_login_form]` | `[custom-login-form]` | Login form |
| `[clu_register_form]` | `[custom-register-form]` | Registration form. With `form="its-id"`, another of the site's [register forms](#register-forms) |
| `[clu_password_lost_form]` | `[custom-password-lost-form]` | "Forgot password" form |
| `[clu_password_set_form]` | `[custom-password-set]` | Set or reset password form, opened from the emailed link |
| `[clu_my_account]` | `[my-account]` | Profile and change-password forms |
| `[clu_auth_buttons]` | `[custom-auth-buttons]` | Login and register links, or the user's name and a logout link |

The forms accept `show_title="true"` to show their title from the settings.

The login form honours `?redirect_to=` for addresses on the same site.

---

## User Approval Workflow

1. A user registers via the front-end form. The account gets the default role of the site (**Settings > General**) and the status **Pending**.
2. The site admin receives an email with what the user filled in and a link to the users awaiting approval. With no address set on the Mail tab, it goes to the site admin email. The Users menu shows how many are waiting.
3. On **Users > Awaiting Approval**, which also lists the registration details, the admin clicks the approve or reject link under the name, or selects several users and uses the bulk actions. Changing the status on the user's own screen does the same.
4. An approved user automatically receives an email with a link to set their password. That link is valid for 7 days. Other reset links stay at the WordPress default of 1 day.
5. After setting a password, the user is logged in.

Rejecting sets the status **Rejected** and emails the user. They cannot register again with the same address. The account stays, so a mistake can be undone by approving, which sends the set-password email. To remove someone without an email, such as a bot, use the normal **Delete** link.

**The plugin stops nobody.** A user who is pending or rejected can log in (once they have a password, for example through a reset), and no page is hidden from them. Blocking a login, a password reset or a page is for the site's theme, which asks where the user stands: `clu_get_user_status( $user_id )`, `clu_user_is_pending( $user )`, `clu_user_is_rejected( $user )`, `clu_user_is_unconfirmed( $user )`, and `clu_account_state_message( $user )` for the key of the matching text on the Messages tab. For example:

```php
add_filter( 'authenticate', function ( $user ) {
    $key = function_exists( 'clu_account_state_message' ) ? clu_account_state_message( $user ) : '';
    return $key !== '' ? new WP_Error( 'not_approved', clu_render_message( $key ) ) : $user;
}, 99 );
```

The emails follow the status only for users who signed up through the plugin's form or the form built into WordPress. A status set on any other user, for example by another plugin, sends nothing.

Registration follows the WordPress setting **Settings > General > Anyone can register**. When it is off, the form and its AJAX endpoint are both closed.

**Automatic approval.** On the **User Roles** tab, **Approve new users automatically** skips the wait: a new user gets the set-password email at once, and the admin gets an email about each one. With the email confirmation on, this happens once the address is confirmed. Users already waiting stay in the list. On a site open to everyone, use it together with the email confirmation and a bot check, or bots get working accounts.

---

## Emails to New Users

Both are off by default, on the **Mail** tab.

**Confirm the email address.** A new user gets an email with a link, and the registration reaches the admin only once they click it. Made-up and mistyped addresses never reach you.

- Until then the account has the status **Email not confirmed**, and it is not counted as awaiting approval
- The link leads to the register page, or the login page if there is none, which says whether it worked. It stays valid for 7 days
- Registering again with the same address sends a new link and keeps the details from the first time. This is the way to get a link that was lost or expired
- Sign-ups through the form built into WordPress confirm their address too
- On the Users list an unconfirmed account offers approving only, for an admin who knows the person. Accounts nobody confirmed can be removed with **Delete**. They are not removed by themselves
- Turning this on or off does not change accounts that already exist, and links already sent keep working

**Registration received.** The user gets an email saying the registration was received and waits for approval. With the confirmation on, it goes out once the address is confirmed.

The texts of both emails, and of the messages around them, are on the Messages tab.

---

## Login Protection

Both are off by default, on the **Login Protection** tab.

**Limit failed logins.** After too many failed attempts from one address, logins from it are refused for a while, even with the right password. The default is 5 attempts and 15 minutes after the last one.

- It counts the failures of every login form of the site: this plugin's, `wp-login.php`, WooCommerce and XML-RPC. A wrong current password on My Account counts too
- It goes by the visitor's address. Behind a proxy or CDN every visitor may arrive from the same address, and one limit would then lock everyone out. The tab shows the address the plugin sees: if it is not yours, return the real one through the `clu_client_ip` filter before turning this on
- An address locked out by mistake opens by itself once the time is up

**Bot check.** Cloudflare Turnstile or Google reCAPTCHA v2 (checkbox), with the keys from the service.

- It appears on this plugin's login, register and lost password forms, and on the same three on `wp-login.php`, including a login form a theme prints with `wp_login_form()`
- Not on forms of other plugins, such as WooCommerce, and not on XML-RPC
- Saving checks the secret key with the service, and a refused key turns the bot check off. The tab then shows the widget with the saved keys: if it shows an error, the site key is wrong, so fix it before logging out
- If the service cannot be reached, or refuses the secret key later, forms are let through without the check and admins see a notice in wp-admin. A bot can cause neither, and refusing everyone would lock the admins out too
- If a wrong site key ever locks you out, rename the plugin's folder over FTP to turn it off

**Two-factor plugins.** With the Two Factor plugin, a user who has a second step is taken from this plugin's login form to its code page, and is logged in once the code is right. It works with Replace wp-login.php on as well. Another two-factor plugin can do the same through the `clu_second_step_url` filter; until then, its users log in on the default form at `/wp-login.php?clu_native=1`.

**Page caches.** A form on a cached page whose security key has grown old renews the key by itself and sends again.

**Changing the email address.** On My Account a field for the current password appears once the email address is changed, and the change is refused without it. A new address is the way to take over an account, through a password reset sent there, so a stolen session alone is not enough. WordPress also emails the old address about the change.

---

## Replacing wp-login.php

Off by default. Create the pages, try the login page in a private window, then turn it on at the bottom of the **Setup Pages** tab. It needs a published login page of this site.

| On `wp-login.php` | With this on |
|---|---|
| The login screen, also from `/wp-admin/` | Your login page, and back to where they were going afterwards |
| Registration, posted or not | Your register page, or the login page if none is set |
| Lost password | Your lost-password page, if set |
| The reset link in emails from WordPress itself, such as **Send password reset** in wp-admin | Your set-password page, if set |
| After logging out with no address of its own | Your page for after logout, or the login page |

Links from `wp_login_url()`, `wp_registration_url()` and `wp_lostpassword_url()` lead to your pages too.

These keep running on `wp-login.php`: logging out, password-protected posts, the admin email check, data requests, recovery mode, the expired-session popup in wp-admin, and actions of other plugins. A login form of another plugin that posts to `wp-login.php` keeps working, and a failed attempt ends on your login page with the error.

**The way back in.** If the login page ever stops working, the default form is still at:

```
/wp-login.php?clu_native=1
```

If a page is deleted or stops being published, that screen goes back to the default form by itself. Recovery mode always uses the default form.

Before turning this on, know that:

- A form that another plugin adds to the default login screen, such as two-factor or CAPTCHA, does not appear on your login page. Those plugins usually check it when the login runs too, which could then fail. Try this on a staging copy first
- The admin email check that WordPress shows from time to time appears only after a login through the default form
- `wp-login.php` still accepts login attempts by post, so this does not protect against password guessing
- It was tested on a single site, not on a multisite network

---

## User Status

Where a user stands is kept on the user, next to their role and **without ever changing it**: None, Email not confirmed, Pending, Approved or Rejected.

- It is how the plugin follows a registration (see [User Approval Workflow](#user-approval-workflow)), and a site can use the same status for a kind of user that an admin approves, such as business customers. The name on the **User Status** tab becomes its title on the admin screens
- An admin sets it on the user's screen, or on the Users list: a column, links under the name, bulk actions, and a link above the list to the pending ones. Whoever may change a user's role may change their status
- It stops nobody from logging in and hides no content
- Emails follow it only for users who signed up through the plugin

For code: `clu_status_label()`, `clu_get_user_status( $user_id )`, `clu_user_status_is_approved( $user_id )` and `clu_set_user_status( $user_id, $status )`, with the status `unconfirmed`, `pending`, `approved`, `rejected` or `''`. The action `clu_user_status_changed` fires after a change with the user ID, the new status and the old one.

---


## Languages

The default texts of the forms, the messages and the emails follow the site's language, under **Settings > General**. The plugin comes with Hebrew.

- A text you wrote in the settings stays as you wrote it. A text left at its default, including one an older version saved as it was, shows in the site's language. Texts are compared by their words, so a default that version 1.0 saved with other line breaks counts as a default too
- Emails go in the language of whoever gets them: the user's own language if they chose one in their profile, or the site's. In a right-to-left language the email is right to left too
- The settings screen itself stays in English
- Pages made by **Create Pages & Save URLs** get their titles and texts in the site's language, even when the admin who clicks works in another one. Their addresses stay in English, such as `/login/`
- On a site with WPML, WPML decides the language of each request, emails included
- The country and region lists, and the choices of Customer Type, stay in English

**Another language:** copy `languages/custom-login-users.pot`, translate it with Poedit or Loco Translate, and put the files in `wp-content/languages/plugins`, where an update does not touch them. A file there comes before the plugin's own.

---

## Templates

To change the HTML of a form, copy its file from the plugin's `forms` folder to a `custom-login-users` folder in the theme, and edit the copy. A child theme's copy comes first, and the `clu_template_path` filter can point anywhere else. The **Setup Pages** tab lists the copies the theme has.

After updating the plugin, compare the copies with its new files: a copy keeps the old form, without what the update added, such as the bot check or a new field.

---

## Register Fields

On **Settings > Custom Login Users > Register Fields**, each field has:

- **On the form**: whether the register form asks for it. The email is always on
- **Label** and **Placeholder**
- **Required**: on the register form, and on My Account too
- **In My Account**: whether users can change it later. A field left out cannot be changed there, even with a forged request

A new site starts with first name, last name and email. Company, Customer Type, Region and Country are built in, ready to be turned on. A site updated from an older version keeps its form as it was.

Custom fields can be text, long text, phone, number, web address, dropdown or checkbox, and are checked on the server by their type. A checkbox label can hold a link, for terms of use. Values are saved as user meta under `clu_field_<name>`, a key that stays the same when the label changes. Removing a field keeps what users already filled in, in the database.

---

## Register Forms

A site can have several register forms, for example one for private customers and one for businesses. They are made on **Settings > Custom Login Users > Register Forms**. Each form has:

- **A role**, which whoever signs up through the form gets at once. Left alone, it is the default role of the site. Roles that can manage the site or its users are not offered, and are never handed out even if a role gains such a capability later
- **Its own fields**, chosen from the list of the Register Fields tab, each one required or optional on this form. The email is on every form. A field meant only for one form is added on the Register Fields tab with "On the form" left off
- **A title and a button text** of its own, or those of the main form when left empty
- **A shortcode**, shown on the tab once the form is saved: `[clu_register_form form="business"]`

The main form is always there: `[clu_register_form]`, with the fields turned on on the Register Fields tab. Only its role is set on this tab.

Everything else is shared by all the forms: the approval, the emails, the messages, the bot check. The email to the admin says which form a registration came through, and the user keeps it as the user meta `clu_register_form`.

The role is given when the form is sent, before anyone approved the user, and the plugin stops nobody from logging in. A site that gives a role prices or content of its own, and wants them only after the approval, checks the status in its theme: `clu_get_user_status( $user_id )`.

---

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin from the **Plugins** menu in WordPress.
3. Go to **Settings > Custom Login Users**.
4. On the **Setup Pages** tab, click **Create Pages & Save URLs**, or create the pages yourself with the shortcodes above and fill in the **Pages URL** tab. The button makes seven pages: five with a form, and two without one that open after logging out and after choosing a password. Those two start with a short text and a link onwards (to the login page, and to My Account or the home page), which you can edit like any page. A page that already exists at the same address is kept as it is.
5. Turn on **Anyone can register** in **Settings > General** if the site takes registrations.

---

## Settings

- **Pages URL** — The login, register, password and redirect addresses
- **Mail** — Sender details, who hears about new registrations, and the emails to new users
- **Login Protection** — The limit on failed logins and the bot check
- **Register Fields** — What the register form asks for
- **Register Forms** — More register forms, each with a role and its own choice of fields
- **Register, Login, Password Reset, Password Lost and My Account Form** — Labels, buttons and validation texts
- **Password Rules** — Requirements for new passwords
- **Messages** — Texts shown to users, and the email templates
- **User Status** — The name shown for the status
- **User Roles** — Automatic approval, and creating or deleting custom roles
- **Setup Pages** — Creates the pages

---

## Filters

| Filter | Use |
|---|---|
| `clu_rate_limit` | Change a limit, or return 0 to turn it off. Receives the limit and the action: `register` (10 an hour per IP), `confirm_resend` (3 new confirmation links an hour per account), `lost_password_ip` (20 per 15 minutes), `lost_password_account` (3 emails per 15 minutes) |
| `clu_client_ip` | Return the real visitor IP on a site behind a proxy or CDN, where `REMOTE_ADDR` is the proxy |
| `clu_approval_link_lifetime` | Seconds an approval link stays valid |
| `clu_user_is_pending` | Decide whether a user counts as waiting for approval |
| `clu_privileged_capabilities` | The capabilities that disqualify the site's default role from going to public sign-ups (Subscriber is used instead) |
| `clu_registration_role` | The role of a new user who signs up through a form that has no role of its own. Receives the default role of the site |
| `clu_register_form_role` | The role a register form gives. Receives the role chosen for the form ('' for none) and the form. A role that can manage the site or its users is refused all the same |
| `clu_posted_field_value` | A value read from a form, for a rule of the site's own, such as the number of digits in a company number. Receives the clean value, the field and the user editing their account (0 on a register form). Return null to refuse the value |
| `clu_send_rejection_email` | Return false to reject users without emailing them. Receives the user |
| `clu_send_received_email` | Decide whether a user gets the registration received email. Receives the Mail tab setting and the user |
| `clu_email_confirm_lifetime` | Seconds a link that confirms an email address stays valid |
| `clu_login_limit` | The limit on failed logins: an array with `on`, `attempts`, and `window` in seconds |
| `clu_second_step_url` | Where a user's second login step takes place, for a two-factor plugin other than Two Factor. Receives the address, the user and where to go after; '' means none |
| `clu_template_path` | The file a form is rendered from. Receives the path and the template name, such as `form_login` |
| `clu_region_countries` | The regions and countries of the Region and Country fields |

The action `clu_user_registered` fires when someone signed up through a register form, before any email about it, with the user ID, the values the form sent and the form. The action `clu_decision_mail_failed` fires when an approval or rejection email could not be sent. The action `clu_user_status_changed` fires after a user's [status](#user-status) changed, with the user ID, the new status and the old one.

---

## Requirements

- WordPress 5.0+
- PHP 7.4+

Tested on WordPress 7.1 with PHP 8.4.

---

## Tests

For a development copy of a site, from the command line. Nothing is kept and no email is sent, since everything runs inside a transaction that is rolled back. Do not run them against a live site all the same.

```bash
php tests/run-tests.php /path/to/wp-load.php --bare
```

`--bare` tests the plugin alone. Without it, the site's active theme and plugins load too, which shows conflicts with them. See the top of the file for the two optional environment variables.

---

## Changelog

### 2.1.0

Added

- **Several register forms, each with a role.** The new Register Forms tab makes more forms next to the main one. Each has a role that new users get at once, its own choice of the fields of the Register Fields tab, its own title and button text, and a shortcode: `[clu_register_form form="its-id"]`. See [Register Forms](#register-forms).
- The main form can be given a role too, instead of the default role of the site.
- The email to the admin says which form a registration came through, on a site with more than one form.
- The filters `clu_register_form_role` and `clu_posted_field_value`, and the action `clu_user_registered`.
- A role that a register form gives cannot be deleted on the User Roles tab.

Nothing changes on a site that makes no second form.

### 2.0.0

The first version of the Coweb line, in its own folder `coweb-custom-login-users`. Sites on 1.9 or earlier are not meant to update to it without a look at what goes away.

Changed

- **Where a registration stands is a status, not a role.** A new user gets the default role of the site and the status Pending (or Email not confirmed). Approving and rejecting are the status links on the Users list and the status field on the user's screen. The **Role Assignments** settings are gone, and the User Status tab no longer has an on/off switch.
- On update, whoever held the Pending User, Approved User, Rejected User or Unconfirmed User role gets the matching status and the default role of the site, and the four roles are removed. A site that had chosen other roles as pending or approved keeps those roles on its users, who get the status next to them.

Removed

- **Every kind of blocking.** Pending, rejected and unconfirmed users are no longer stopped from logging in or resetting a password, their sessions are not ended, the admin bar and wp-admin are no longer closed to them. A site that wants any of it does it in its theme; see [User Approval Workflow](#user-approval-workflow).
- **Restricted content**: the Access box, the `[clu_approved_only]` shortcode, the page for users without access and the two messages. Pages that were restricted are open once this version is active. The `_clu_restricted` mark stays on them, so a theme can go by it.
- `clu_get_role_slug()`, `clu_decide_user()`, `clu_blocked_account_message()` (now `clu_account_state_message()`), `clu_user_can_view_restricted()` and the filters of restricted content.

### 1.9.0

Added

- **User Status**: a status kept on the user, next to their role and without changing it, for a kind of user that an admin approves, such as business customers. It is turned on with a checkbox on the new **User Status** tab, where that kind of user also gets its name. The status is set on the user's screen and on the Users list, the Approved status opens restricted content, and `clu_user_status_changed` tells other code about a change. It does not stop a login and sends no email. See [User Status](#user-status).

Off by default, so nothing changes on a site that does not turn it on. The approval of new registrations through roles is as it was.

### 1.8.0

Added

- **Create Pages & Save URLs** no longer leaves the Logged Out and Password Updated pages empty. Each starts with a line that says what happened and a link onwards: Logged Out to the login page, Password Updated to My Account, or to the home page when there is no My Account page. The text is ordinary page content, to edit or replace.
- The pages it creates get their titles and texts in the site's language, with Hebrew included, and not in the language of the admin who clicks. Addresses stay in English. The Setup Pages table shows the titles the pages will get.

Pages that already exist are not changed, so a site that created its pages with an earlier version keeps them as they are.

### 1.7.1

Before updating a live site

- On a site updated from version 1.0, texts that 1.0 saved as they were now show the current wording, for example the email to the admin, which gains the registration details and the link to the users waiting. Texts written by hand stay as they are.

Fixed

- Version 1.0 saved every default text along with the ones an admin changed, often with other line breaks. Those saved defaults kept showing after an update, such as the old email to the admin, without the registration details and the link to the users waiting. They now give way to the current defaults, and to the site's language. Texts an admin wrote stay. Found by updating copies of two client sites.
- On a site with WPML, turning on Replace wp-login.php made every page with a login link hang: WPML asks for the login link while it resolves an address, and the plugin resolved the address of the login page to build that link, so the two called each other without end. Found on a copy of a client site.
- A user with a second login step in the Two Factor plugin could not log in through this plugin's form: Two Factor printed its code page into the form's answer, and the form showed a network error. Such a user now goes on to the code page.
- When no address is set on the Mail tab, the site admin address is trimmed before the email goes to it.
- The tests run on a copy of any site: they act as its first administrator, leave out a copy of the plugin the site has installed, and skip what the site cannot run, such as Hebrew when WordPress in Hebrew is not installed.

Tested by updating copies of two client sites from version 1.0, with their themes and plugins, on PHP 8.2; next to Two Factor and WP Super Cache; and on PHP 8.4 and 8.5.

### 1.7.0

Before updating a live site

- On a site in Hebrew, texts left at their English defaults now show in Hebrew: the forms, the messages and the emails. Texts written in the settings stay as they are. Check the forms after the update.
- Emails to users go in their own language, or the site's, rather than in the language of the admin who approved them.

Added

- Hebrew translation, and the plugin can be translated into other languages.
- **Approve new users automatically**, off by default, on the User Roles tab.
- Templates: a theme can replace the HTML of any form. The Setup Pages tab lists its copies.
- Saving the Messages tab or a form tab no longer stores the defaults, so they keep following the language and later versions.

### 1.6.0

Before updating a live site

- On My Account, changing the email address now needs the current password. A field for it appears once the address is changed.

Added

- **Limit failed logins**, off by default, for every login form of the site. See Login Protection above.
- **Bot check** with Cloudflare Turnstile or Google reCAPTCHA v2, off by default, on the login, register and lost password forms of this plugin and of `wp-login.php`.
- A wrong current password on My Account counts as a failed login.

### 1.5.0

Added

- **Confirm the email address**, off by default: new users confirm their address through an emailed link before the registration reaches the admin. See Emails to New Users above.
- **Registration received email**, off by default.
- The Unconfirmed User role, added only once the confirmation is used.

Nothing changes on an existing site until one of them is turned on on the Mail tab.

### 1.4.0

Before updating a live site

- On a site where anyone can register, sign-ups through the form built into WordPress, at `wp-login.php?action=register`, now wait for approval like all others. WordPress no longer emails those users about a password, since they cannot set one until approved.

Security

- That form was a way around the approval step: it created a working account in the default role, and no admin heard about it.

Added

- **Replace wp-login.php**, off by default: `wp-login.php` and the links to it lead to your pages. See Replacing wp-login.php above.
- The login page shows an error when a login posted to `wp-login.php` failed and was sent there.

### 1.3.0

Added

- Restricted content: an Access box for a whole page or post, and the `[clu_approved_only]` shortcode for part of one. See what it does and does not cover under Restricted Content above.
- A setting for the page that logged-in users without access are sent to, and two new messages.

Nothing changes on an existing site until a page is restricted.

### 1.2.0

Before updating a live site

- The register form stays as it is: on the update, all its fields stay on and required, with their saved labels and placeholders. Those labels move from the Register Form tab to the new Register Fields tab.
- A field that is required on the register form is now required on My Account too. Until now, Company was optional there. To keep it so, make it optional on the Register Fields tab.
- The functions `clu_register_placeholder()` and `clu_get_customer_type_options()` are gone, in case a theme calls them. Choices and placeholders from ACF options keep working.
- The default of the email to the admin changed. A site that wrote its own text keeps it, and `{edit_user_link}` keeps working in it.

Added

- Approve and Reject links and bulk actions on the Users list, a rejection email, and a Rejected User role that cannot log in.
- A count of users awaiting approval on the Users menu, a link to their list, and a column there with what they filled in. The email to the admin lists it too.
- The Register Fields tab: fields on and off, required or optional, in any order, your own custom fields, and which of them appear on My Account.
- The screen reports when an approval or rejection email could not be sent.

Fixes

- A user moved to a role that may not log in stayed logged in until the session ended. Their sessions now end at once.
- Without ACF, the placeholders of a new form were fixed English text. They now come from the settings.

### 1.1.0

Before updating a live site

- All plugin functions now start with `clu_`, so a theme that calls an old name such as `render_login_form()` needs updating. The same goes for the asset handles `validation-js` and `templates-css` (now `clu-validation` and `clu-forms`), the JS object `ajaxData` (now `cluData`), and the settings screen address (now `page=clu-settings`). Shortcodes, AJAX actions and saved settings keep their names.
- A pending user can no longer log in. If a site uses a role that real users hold as its pending role, such as Subscriber, those users are blocked too. Choose a dedicated role on the User Roles tab, or use the `clu_user_is_pending` filter.
- New sites start with a minimum password length of 8 instead of 6. Sites that saved their rules keep them.

Security

- A pending user could get a reset link from the lost-password form and log in without approval. Pending users are now blocked from logging in and from resetting a password, on every form of the site.
- The registration endpoint stayed open when registration was turned off.
- The 7 day link lifetime applied to every password reset on the site, admins included. It now applies to approval emails only.
- Login and lost-password answers no longer reveal whether an account exists.
- A role such as Administrator could be chosen as the role for public sign-ups.
- Added the bot trap and the limits listed above, and removed usernames from the error log.
- The company field from wp-admin was saved without being sanitized.

Fixes

- Names with no Latin letters, such as Hebrew names, could not register at all.
- Passwords with characters such as `<` or `%41`, or double spaces, did not match between this plugin and WordPress or WooCommerce, including between the set-password and change-password forms of the plugin itself.
- A plugin function name clashed with other code using common names, which crashed the site. All functions now carry the `clu_` prefix.
- The register form crashed without ACF, and depended on jQuery being loaded before it.
- A `%` in an email subject template crashed registration, after the user was already created.
- Registrations notified nobody when no address was set on the Mail tab.
- `?login[]=x` on a form page crashed the page.
- Long names could not register, and the required fields were checked only in the browser.
- The min-length message stayed at its old number after the rule changed, and the set-password form was shown even for a dead link.
- After login, `?redirect_to=` was ignored, and with no redirect set on the Pages URL tab the user stayed on the form.
- Links are no longer forced to https on a site that runs on http.
- Roles deleted on the settings screen came back on the next request, and roles still in use could be deleted.

Improvements

- Forms keep working on cached pages, by fetching a fresh nonce when the printed one has expired.
- Added a confirm-password field on the set-password form, and a Remember Me checkbox on login.
- The My Account form and the hard-coded texts of the forms can now be edited in the settings.
- Removed the settings that had no effect: the Errors Messages tab and unused fields on the form tabs.
- The password rules now count characters, not bytes, and accept any symbol as a special character.
- The settings screen remembers its tab after saving, updates the Setup Pages table without a reload, and warns about an existing page that has no form shortcode. Its assets load on that screen only.
- Added `uninstall.php`, removed the debug output, and removed the retired code.

### 1.0

First release.
