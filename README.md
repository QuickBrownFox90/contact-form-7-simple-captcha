# ContactForm7 Simple Captcha

A small "I am not a robot" captcha for [Contact Form 7](https://wordpress.org/plugins/contact-form-7/). One checkbox look-alike in the form, checked on the server. No account, no external service, no cookies, no JavaScript of its own.

## About

ContactForm7 Simple Captcha is aimed at WordPress developers and administrators who want a reliable, no-nonsense Captcha in their forms — without upsells, cloud services, CDNs, or UI bloat that comes from a freemium model.

> This is a side project maintained by one person in their spare time — not a product with a roadmap, a support team, or a commitment to feature requests. If this plugin doesn't fit your workflow exactly, the codebase is intentionally kept clean and forkable. GPLv2 applies; take it and run.

### Why

Most captchas ask your visitors for something: a click on traffic lights, a request to a third party, a cookie banner entry. Most spam bots, on the other hand, are simple: they fill every field they find and send the form, or they replay a request they have seen once.

This plugin answers that with the least possible: the visitor ticks one box. A bot that fills the form without understanding it does not tick the right one. Everything happens on your server, so there is nothing to put into a privacy statement.

## How it works

The captcha is a group of five radio buttons, hidden with CSS, and one label, "I am not a robot", that points to **one** of them. A visitor clicks the label and so selects the right button. The browser sends its value with the form, and the plugin checks it when Contact Form 7 asks whether a submission is spam.

What makes this hard to automate:

- **Only one value is right, and it is never the same.** The valid values are calculated (HMAC) from a random secret that exists only in your database, and from the day. A value is valid for a number of days (7 by default); every page uses one of them at random. There is no value in the code that a bot could learn once and use on every site.
- **The position is not guessable.** The first radio button is always wrong, the last one is wrong and preselected, the right one sits somewhere in between. A bot that takes the first, the last or the preselected one, or ticks all of them, fails.
- **Nothing looks logical.** The field name, the ids, the values and the CSS classes are random-looking strings, different on every site.
- **Page caches work.** The valid values are not stored per page, so a page that was cached a few days ago is still accepted, as long as it is younger than the validity (see [Settings](#settings)).

A submission without the right value is treated like any other spam by Contact Form 7: the form shows the spam message of the form, no mail is sent.

## Features

- An "I am not a robot" captcha in every Contact Form 7 form, with no setup
- Placed in front of the submit button (an `<input type="submit">`, a `<button type="submit">` or a `<button>` without a type), or at the end of the form if there is none
- The form tag `[simple_captcha]` places it exactly where you want it, with a button in the form-tag generator of the form editor
- Valid values calculated from a secret of the site, rotating by the day, cache friendly
- A settings page under **Contact → CF7 Simple Captcha**: highlight color of the checkmark, validity in days, a button for a new secret
- Updates from the GitHub releases, shown like any other plugin update
- Translations: German (formal and informal), French and Dutch complete; Spanish, Italian, Polish and Brazilian Portuguese for the label. A regional locale (de_AT, fr_BE, es_MX …) uses the language of its family
- The warning "unsafe email without protection" of the Contact Form 7 form check is hidden

## Requirements

- WordPress 6.5 or later
- PHP 8.1 or later
- ContactForm7 installed and active. The plugin cannot be activated without it

## Installation

1. Download the latest `contact-form-7-simple-captcha.zip` from the [Releases page](https://github.com/QuickBrownFox90/contact-form-7-simple-captcha/releases).
2. In your WordPress admin go to **Plugins → Add New → Upload Plugin**, select the ZIP and click **Install Now**.
3. Activate the plugin. Contact Form 7 has to be active already.

That is all: from now on every Contact Form 7 form has the captcha. There is nothing to configure; the secret is created at the first use.

Once installed, future updates appear in the standard WordPress update flow — no manual download needed.

## Updates

ContactForm7 Simple Captcha looks for new versions in the releases of its GitHub repository and offers them **like any other plugin update** (Plugins, "Check for updates" in the row of the plugin forces a new look). It asks GitHub in the admin and in cron, at most once a day, and never for a visitor of the site.

- An update does not change the secret, the settings or the values that were handed out: forms in a cache stay valid.
- Pre-releases are offered as well; the filter `wpcf7sc_updater_prereleases` switches that off. The constant `WPCF7SC_DISABLE_UPDATER` or the filter `wpcf7sc_updater_enabled` switches the updater off.
- Only the `contact-form-7-simple-captcha.zip` of a release is taken, and only if it comes from the repository itself.

## For administrators

### Put the captcha into a form

Nothing to do: the captcha is added to every form by itself. If you want it at a certain place, open the form in the editor (**Contact → Contact Forms**), put the cursor where it should be, click **simple captcha** in the row of the tag buttons and insert the tag `[simple_captcha]`. A form that has the tag gets the captcha exactly there and nowhere else.

### Check that it works

1. Open a page with the form, tick "I am not a robot", send. The form is sent as usual.
2. Reload and send without ticking. The form must show its spam message and send no mail.

A visitor who forgets to tick the box sees the same spam message that a bot sees: the captcha gives no hint what is wrong. Put a sentence next to the form if your visitors need one.

### Settings

**Contact → CF7 Simple Captcha**

- **Highlight color**: the color of the checkmark, a hexadecimal value with a leading `#`. Empty means the color of the text.
- **Validity in days** (2 to 90, default 7): how long a value stays valid. A page is accepted for at least this number of days **minus one** after it was created. If you use a page cache, this value must be larger than the longest time a page stays in the cache plus one day. Otherwise a visitor of an old cached page is turned away.
- **Create a new secret**: replaces the secret. All values that were handed out become invalid at once, and the field name and CSS classes change. Use it when you think that someone has learned how a bot can pass, or after a leak of the database. **Clear the page cache afterwards**: pages in the cache still carry the old values and would be rejected.

The same works without the page: `wp option delete wpcf7sc_secret` removes the secret, a new one is created at the next request.

### When visitors say that they cannot send the form

1. Is a page cache or a CDN in front of the site that keeps pages longer than the validity? Clear the cache or raise the validity.
2. Did somebody create a new secret or delete the option after the page was cached? Clear the cache.

### Coming from SpamProtect

The old plugin "Contact Form 7 anti spam extension" has no connection to this one, and its settings are not taken over. Deactivate it before you activate this one: the two should not add a captcha to the same form. Set the highlight color again if you used one.

## Limits

Be honest about what this is.

- **It stops simple bots.** Bots that fill every field, take the first, last or preselected option, replay a request or send the same values to many sites. It does not stop a bot that was written for this plugin, that loads the page, reads the label, finds the radio button it points to and sends its value. It does not stop people who send spam by hand.
- **Rotating values do not hide the right one from a bot that reads the page.** They make a value that was learned useless on other sites, and useless after the validity runs out.
- **It needs CSS.** The buttons are hidden with an inline style in the `<head>` of every page.
- **Only Contact Form 7**, only forms that are printed by it.

## Stored data and hooks

Options (not removed when the plugin is deleted):

| Option | Content |
|---|---|
| `wpcf7sc_secret` | The random secret (64 characters). Never shown, never in the code |
| `wpcf7sc_lifetime_days` | Validity in days |
| `wpcf7sc_highlight_color` | Highlight color |

Filters and constants:

| Name | Effect |
|---|---|
| `wpcf7sc_lifetime_days` | The validity in days (wins over the option; between 2 and 90) |
| `wpcf7sc_updater_enabled` | `false` switches the updater off |
| `wpcf7sc_updater_prereleases` | `false` hides pre-releases |
| `WPCF7SC_DISABLE_UPDATER` | Constant that switches the updater off |

## Status

Version 1.0.0.

## License

GPL v2 or later, see [LICENSE](LICENSE).
