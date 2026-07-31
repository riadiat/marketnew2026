# Security findings — handover snapshot, reviewed 2026-07-31

## 1. Obfuscated PHP files (unresolved — blocks go-live)

A scan of all 4,627 PHP files found five that are machine-obfuscated: variable
names are random hex, and every function name is rebuilt character-by-character
from a 104-byte lookup table stored in `$GLOBALS`. This is what code packers and
webshells both look like; static analysis alone cannot separate the two.

| File | Size | Upstream size |
|---|---|---|
| `public_html/admin/controller/extension/dashboard/activity.php` | 20,359 B | 4,403 B |
| `public_html/catalog/language/en-gb/mail/order_add.php` | 15,082 B | 1,436 B |
| `public_html/seller-cp/language/en-gb/design/translation.php` | 14,833 B | *(no upstream — custom)* |
| `public_html/seller-cp/model/localisation/order_status.php` | 16,750 B | *(no upstream — custom)* |
| `public_html/seller-cp/model/marketing/marketing.php` | 17,377 B | *(no upstream — custom)* |

The first two are stock OpenCart files that upstream ships as plain readable
code. `catalog/language/en-gb/mail/order_add.php` in particular is an email
template — an array of translated strings — and there is no legitimate reason
for it to contain 15 KB of packed logic. The Arabic sibling
(`catalog/language/ar/mail/order_add.php`) is a normal 1,925-byte language file.

`admin/controller/extension/dashboard/activity.php` was executing on **every
admin dashboard load** — it was the source of the `Undefined array key
"rddd1cb"` warning that first exposed it.

### Done

Both stock files were replaced with clean OpenCart 3.0.2.0 upstream copies. The
admin dashboard renders correctly afterwards with zero warnings, confirming the
obfuscated version provided nothing the application needed.

Originals preserved for forensics in `.quarantine/2026-07-31/` — do not deploy
that directory.

### Still to do

The three `seller-cp/` files have no upstream to compare against. `seller-cp` is
custom/commercial code, so these *may* be deliberately packed by the extension
vendor to protect their IP — that is common practice. **Ask whoever supplied the
seller panel to confirm, in writing, that they obfuscated these three files.**
If they did not, treat the install as compromised.

Either way, before going live:

- Rotate every credential in the database — admin users, API tokens, payment
  gateway keys, SMTP.
- Review `oc_user` and `oc_api` for accounts nobody recognises.
- Do not reuse the old server's filesystem; deploy fresh from the repo.

## 2. Leaked database credentials

The handover `config.php` files contained a production MySQL password in
plaintext (user `forge`). Those files are now gitignored and the credentials are
read from the environment — but the password itself must be considered public
and **rotated before the new server goes live**.

## 3. `config_error_display` is on

`oc_setting.config_error_display = 1` in the dump, which renders PHP warnings —
including file paths — into the storefront HTML for customers. Set it to `0`.

```sql
UPDATE oc_setting SET value='0' WHERE `key`='config_error_display';
```

## 4. World-writable files

The snapshot is `777` throughout, files and directories alike. Any PHP process
on the box can rewrite application code. See the permissions step in
[DEPLOYMENT.md](DEPLOYMENT.md).

## 5. OpenCart 3.0.2.0 is nine years unpatched

3.0.2.0 is from 2017; the 3.x line ended at 3.0.4.0 with security fixes this
install does not have. Upgrading is a project of its own — the custom
`seller-cp` and the `ryadiatshop` theme both need porting — but it should be
scheduled, not deferred indefinitely.

## Clean

- No PHP files under `public_html/image/`, `storage/upload/`, or
  `public_html/system/storage/upload/` — no uploaded shells in the usual spots.
- No `eval(base64_decode(...))`, `create_function`, or `assert()` string-eval
  anywhere in the tree.
- `public_html/.htaccess` correctly denies `.tpl`, `.twig`, `.ini`, `.log`, and
  `system/storage/`.
