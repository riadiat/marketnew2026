#!/usr/bin/env bash
# Reset an admin password using OpenCart 3's salted SHA1 scheme.
#
#   ./scripts/set-admin-password.sh <password> [username]
#
# Intended for local development. Point it at production only if you mean it.

set -euo pipefail

PASS="${1:?usage: $0 <password> [username]}"
USER_NAME="${2:-admin}"
DB_NAME="${DB_NAME:-ewmarket}"
DB_USER="${DB_USER:-ewmarket}"
DB_PASS="${DB_PASS:-ewmarket}"

mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" <<SQL
UPDATE oc_user SET salt = SUBSTRING(MD5(RAND()), 1, 9) WHERE username = '$USER_NAME';
UPDATE oc_user
   SET password = SHA1(CONCAT(salt, SHA1(CONCAT(salt, SHA1('$PASS')))))
 WHERE username = '$USER_NAME';
SQL

echo "password for '$USER_NAME' updated"
