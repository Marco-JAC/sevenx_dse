# Changelog

All notable changes to sevenx_dse. Earlier releases are described on
<https://github.com/se7enxweb/sevenx_dse/releases>.

## 1.1.20 (2026-11-01)

- Updated: SQLite: every page of the editor with a database selected opens in a fraction of a second instead of
  several seconds on a large database. The navigation panel asks the SQLite driver for its table list without row
  counts (`table_status('', true)`), as the other drivers already answered, but the driver ignored the request and
  ran `COUNT(*)` on every table and view each time (257 tables and 3 GB on the reference site: 2.7-3.1 s per page).
- Updated: SQLite: the row counts of the database page are kept for reuse in `var/<site>/cache/dse/`, keyed by the
  database file and the size and time of the file and its -wal file. While nothing has been written since, they are
  shown as exact; after a change they are shown for up to `dse.ini [DSESettings] RowCountCacheTTL` seconds (default
  120, 0 = never) marked "~" and "approximate, as of <time>", then counted again. The footer of the Rows column says
  when they were counted and links to counting them again. Views are no longer counted (their row was never shown),
  and the request that counts sets `PRAGMA query_only` on its connection.
- Updated: The editor's own scripts run again inside the administration. Its inline scripts carried a nonce of their
  own, which the administration's Content-Security-Policy does not list, so the browser blocked all of them: the
  table figures stayed "?", and the check boxes, the sticky footer and the confirmations before Drop and Truncate did
  not work. Embedded, the editor now uses the nonce of the policy the kernel has set for the response.

## 1.1.15 (2026-10-22)

- Updated: The "DS Editor" admin tab has an icon: `Icon=brush` in `menu.ini [Topmenu_dse]`, drawn by the admin4
  design before the tab's name.
- Fixed: The tab checked the policy `dse/dashboard`, a function the `dse` module does not have, so a role limited to
  `dse/dse` could open the editor but never saw the tab. It now checks `dse/dse`.
- Updated: README.md rewritten as a complete guide: features, requirements, installation with Composer and by hand,
  every setting, how to switch the tab on, usage step by step, permissions, translations, rebuilding the AdminNeo
  assets, upgrading, known limits, troubleshooting, contributing, license and credits.
- Updated: composer.json states the support links, PHP 8.0 or later, `extra.installer-name` and current keywords;
  obsolete suggestions (`ext-mysql`, `ext-suhosin`) removed.
- Updated: ezinfo.php names the included AdminNeo with its version, copyright and license; ezinfo.php and
  extension.xml state version 1.1.15.
- Added: This CHANGELOG.md.
