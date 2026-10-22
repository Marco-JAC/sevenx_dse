# Changelog

All notable changes to sevenx_dse. Earlier releases are described on
<https://github.com/se7enxweb/sevenx_dse/releases>.

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
