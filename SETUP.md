# KolEntityIds — Setup Guide

This document covers everything that needs to be configured outside the extension itself.

## Prerequisites

- MediaWiki >= 1.40
- [Extension:Cargo](https://www.mediawiki.org/wiki/Extension:Cargo) >= 3.5 installed and enabled

## 1. Enable the extension

Add to `LocalSettings.php`:

```php
wfLoadExtension( 'Cargo' );
wfLoadExtension( 'KolEntityIds' );
```

## 2. Configure entity types

Add to `LocalSettings.php` after the above, one entry per entity type:

```php
$wgKolEntityIdTypes = [
    'Monster' => [
        'namespaceId'    => 3000,   // must be an even integer; talk namespace = namespaceId + 1
        'cargoTable'     => 'Monsters',
        'cargoIdField'   => 'monsterid',
        'cargoLinkField' => 'link', // optional — the template param that holds the target article title
    ],
    'Item' => [
        'namespaceId'    => 3002,
        'cargoTable'     => 'Items',
        'cargoIdField'   => 'itemid',
        'cargoLinkField' => 'link',
    ],
    // add further entity types following the same pattern
];
```

**Namespace ID notes:**
- IDs must not conflict with existing MediaWiki namespaces (built-in IDs go up to ~15; extensions typically start at 100 or 3000+).
- Check `Special:Version` on your wiki for namespaces already in use.

**`cargoLinkField` behaviour:**
- If present and non-empty in the Cargo row, it is used as the redirect target.
- If absent or empty, the redirect target is derived by stripping the `Data:` prefix from the Data page title (e.g. `Data:Zmobie (monster)` → `Zmobie (monster)`).

## 3. Permissions

The extension automatically sets entity namespaces (and talk namespaces) to require the `editprotected` right (i.e. admins only), since they are redirect-only and real pages should not be created in them.

## 4. Add Cargo declarations to format templates

For each entity type, find the format template that receives the entity's parameters and add the following.

### Example: `Template:Combat/meta` (monsters)

Inside the `<noinclude>` block, add the Cargo table declaration:

```
{{#cargo_declare:_table=Monsters
|monsterid=Integer
|link=String
}}
```

At the very start of the `<includeonly>` block, add the Cargo store call:

```
{{#cargo_store:_table=Monsters|monsterid={{{monsterid|}}}|link={{{link|}}}}}
```

Repeat this pattern for each entity type, matching `_table`, field names, and parameter names to your `$wgKolEntityIdTypes` configuration.

### Why the format template and not the data pages?

The format template (e.g. `Combat/meta`) is transcluded by every data page via `{{{{{format}}}|...}}`, so adding `#cargo_store` there means all data pages store their data automatically whenever they are parsed — no per-page edits required.

## 5. Populate Cargo tables from existing pages

After adding `#cargo_declare` to a template, the table will be empty until existing pages are re-parsed. Do one of the following:

**Via the template page (recommended):**
1. Navigate to the template page (e.g. `Template:Combat/meta`).
2. Click the **"Create data"** or **"Recreate data"** tab.
3. Click **OK** — Cargo queues a re-parse job for every page that transcludes the template.
4. Run `php maintenance/runJobs.php` if your wiki does not process the job queue automatically.

**Via Special:CargoTables:**
Administrators can click the **"recreate data"** link next to each table in `Special:CargoTables`.

**Via maintenance script:**
```bash
php maintenance/run.php extensions/Cargo/maintenance/cargoRecreateData.php --table Monsters
```

This only needs to be done once per table. Future page saves update the table automatically.

## 6. Verify

1. Go to `Special:CargoTables` and confirm your table exists with the expected number of rows.
2. Pick a known entity ID (e.g. `31` for the Zmobie monster).
3. Visit `https://yourwiki.example.com/wiki/Monster:31` — you should be redirected (HTTP 301) to the correct article.
4. Visit an ID for an entity with no `link` param — confirm the `Data:` prefix strip fallback works.
5. Visit a non-existent ID (e.g. `Monster:99999`) — confirm a normal "page does not exist" response, no error.
6. Visit a non-numeric title (e.g. `Monster:foo`) — confirm no redirect occurs.
