#!/usr/bin/env bash
# Rewrite absolute URLs left in the database by the old ewmarket.sa install.
#
#   ./scripts/rewrite-urls.sh            # report only
#   ./scripts/rewrite-urls.sh --apply
#
# Connects with whatever ~/.my.cnf provides, so no credentials go on the command
# line. Set DB_NAME if that file does not already select a database.
#
# URLs become root-relative rather than pointing at the current host, so this
# does not need running again the next time the site moves.
#
# Safety comes from the REPLACE patterns, not the WHERE clause: every pattern
# includes the '//' separator, so an address like help@ewmarket.sa sitting in
# CMS text is never a substring match and comes through untouched. The WHERE is
# only there to skip rows that need no work.
#
# Left alone entirely:
#
#   oc_order.store_url, oc_order_history.comment  — 266 orders record the host
#     they were placed on. That is history; rewriting it makes the records lie.
#   oc_user.email, oc_newsletter.email            — addresses, not URLs.
#   config_email, config_mail_smtp_username       — addresses; see the note
#     printed at the end.
#   quickcheckout_license_domains                 — bare hostnames for a licence
#     check, not links.
#   oc_session.data, oc_apple_*                   — expired sessions, and an
#     uninstalled extension's tables.

set -euo pipefail

OLD="${OLD_DOMAIN:-ewmarket.sa}"
APPLY=0
[[ "${1:-}" == "--apply" ]] && APPLY=1

MYSQL=(mysql)
[[ -n "${DB_NAME:-}" ]] && MYSQL+=("$DB_NAME")

SQL_FILE="$(mktemp)"
trap 'rm -f "$SQL_FILE"' EXIT

# Quoted heredoc: bash does no backslash processing, so what MySQL receives is
# exactly what is written here. __OLD__ is filled in afterwards.
#
# Four forms per scheme: with and without www, plain and JSON-escaped. Settings
# are stored as serialised JSON, where a slash is written \/ — in a SQL literal
# that backslash has to be doubled.
cat > "$SQL_FILE" <<'SQL'
SET @old = '__OLD__';

UPDATE oc_banner_image
   SET link = REPLACE(REPLACE(REPLACE(REPLACE(link,
         CONCAT('https://www.', @old), ''), CONCAT('https://', @old), ''),
         CONCAT('http://www.',  @old), ''), CONCAT('http://',  @old), '')
 WHERE link LIKE CONCAT('%', @old, '%');

UPDATE oc_information_description
   SET description = REPLACE(REPLACE(REPLACE(REPLACE(description,
         CONCAT('https://www.', @old), ''), CONCAT('https://', @old), ''),
         CONCAT('http://www.',  @old), ''), CONCAT('http://',  @old), '')
 WHERE description LIKE CONCAT('%', @old, '%');

UPDATE oc_setting
   SET value = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(value,
         CONCAT('https:\\/\\/www.', @old), ''), CONCAT('https:\\/\\/', @old), ''),
         CONCAT('http:\\/\\/www.',  @old), ''), CONCAT('http:\\/\\/',  @old), ''),
         CONCAT('https://www.',     @old), ''), CONCAT('https://',     @old), ''),
         CONCAT('http://www.',      @old), ''), CONCAT('http://',      @old), '')
 WHERE value LIKE CONCAT('%', @old, '%')
   AND `key` NOT IN ('config_email', 'config_mail_smtp_username',
                     'quickcheckout_license_domains');
SQL

sed -i "s/__OLD__/$OLD/g" "$SQL_FILE"

report() {
    "${MYSQL[@]}" -N -B <<EOF
SELECT CONCAT(RPAD(t, 42, ' '), n) FROM (
  SELECT 'oc_banner_image.link' t,
         COUNT(*) n FROM oc_banner_image WHERE link LIKE '%$OLD%'
  UNION ALL SELECT 'oc_information_description.description',
         COUNT(*) FROM oc_information_description WHERE description LIKE '%$OLD%'
  UNION ALL SELECT 'oc_setting.value (excl. email/licence)',
         COUNT(*) FROM oc_setting WHERE value LIKE '%$OLD%'
           AND \`key\` NOT IN ('config_email','config_mail_smtp_username',
                              'quickcheckout_license_domains')
) x;
EOF
}

echo "old domain: $OLD"
[[ $APPLY -eq 1 ]] && echo "mode: APPLY" || echo "mode: report only (pass --apply to write)"
echo
echo "rows containing the old domain:"
report | sed 's/^/  /'

if [[ $APPLY -eq 1 ]]; then
    "${MYSQL[@]}" < "$SQL_FILE"
    echo
    echo "after:"
    report | sed 's/^/  /'
    echo
    echo "(any non-zero left is an email address inside content — edit those in admin)"
fi

cat <<EOF

Still needs a decision — the store cannot send mail:

  config_email               info@$OLD
  config_mail_smtp_username  noreply@$OLD

$OLD does not resolve and has no MX records, so order confirmations, password
resets and seller notifications all fail. Receiving servers reject mail whose
sender domain does not exist, so pointing SMTP somewhere else does not fix it.
Move both to an address on a domain that works — riadiat.sa already has Google
Workspace MX:

  UPDATE oc_setting SET value='info@riadiat.sa'    WHERE \`key\`='config_email';
  UPDATE oc_setting SET value='noreply@riadiat.sa' WHERE \`key\`='config_mail_smtp_username';
EOF
