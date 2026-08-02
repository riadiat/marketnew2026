#!/usr/bin/env bash
# Turn the handover dump into something fit for a managed MySQL service.
#
#   ./scripts/prepare-dump.sh database/ewmarket_08_01_2024.sql [out.sql.gz]
#
# Two changes:
#
#   MyISAM -> InnoDB. 133 of the 157 tables are MyISAM, which has no
#   transactions and no crash recovery. More to the point, managed providers
#   build backups and point-in-time recovery on InnoDB's redo log — MyISAM
#   tables are outside that and can be silently unrecoverable. There are no
#   FULLTEXT indexes in this schema, so nothing depends on MyISAM.
#
#   Session rows dropped. oc_session is ~143 MB of expired sessions from the
#   old site. Restoring them serves no purpose and slows the import.

set -euo pipefail

IN="${1:?usage: $0 <dump.sql> [out.sql.gz]}"
OUT="${2:-${IN%.sql}-prepared.sql.gz}"

[[ -f "$IN" ]] || { echo "no such file: $IN"; exit 1; }

echo "in   $IN ($(du -h "$IN" | cut -f1))"

# The INSERTs for oc_session sit between its LOCK TABLES and UNLOCK TABLES, so
# dropping that one block leaves the CREATE TABLE and every other table intact.
awk '
    /^LOCK TABLES `oc_session` WRITE;/ { skip = 1 }
    skip && /^UNLOCK TABLES;/          { skip = 0; next }
    !skip                              { print }
' "$IN" \
| sed -e 's/ENGINE=MyISAM/ENGINE=InnoDB/g' \
| gzip -c > "$OUT"

echo "out  $OUT ($(du -h "$OUT" | cut -f1))"
echo
echo -n "MyISAM left: "; zcat "$OUT" | grep -c "ENGINE=MyISAM" || true
echo -n "InnoDB:      "; zcat "$OUT" | grep -c "ENGINE=InnoDB" || true
echo -n "CREATE TABLE:"; zcat "$OUT" | grep -c "^CREATE TABLE" || true
echo
echo "load it with:"
echo "  zcat $(basename "$OUT") | mysql --defaults-file=~/.my.cnf <database>"
