Awesoft Developer Module for Magento2
===

A module for Magento 2 that integrates various developer tools. Everything is active in developer mode only.

# Installation
- Composer
  ```shell
  composer require awesoft/module-developer
  ```

# Features
- [x] Whoops error handler
- [x] Developer debug bar

Settings: Stores > Configuration > Advanced > Developer > Awesoft Developer Settings.

## Whoops error handler
- Pretty error page for storefront/admin exceptions, JSON output for AJAX and `Accept: application/json` requests
- Exceptions are still written to `var/log/exception.log`
- "Open In Editor" links for the bundled editors, or a custom URL template using `%file` and `%line`
- Editor path mapping, one `server=local` pair per line, e.g. `/app=/Users/me/project` for Docker setups
- A "Magento" table with version, mode, area, store, full action name and route params
- Cookies and GET/POST/SERVER/ENV keys containing pass, secret, key, token, auth, session or cookie are masked;
  add more keys under "Extra Hidden Keys"

## Developer debug bar
A toolbar at the bottom of HTML pages, with panels for request, performance, database, layout, events and
environment. It respects the Developer Client Restrictions (Allowed IPs) and can be turned on per area
(storefront/admin).

- Each tab shows one key value (status code, time, query count, block count, event count); hover a tab or
  open its panel for the full summary
- Tab badges are coloured by status: green/amber/red for the HTTP status, response time (1s/3s) and query
  count (over 100); thresholds are constructor arguments of the collectors, adjustable in `di.xml`
- Panels can be resized by dragging their top edge and filtered with the search box; the open tab, panel
  height and collapsed state are remembered
- Database queries need the DB profiler enabled in `app/etc/env.php` under `db/connection/default`:
  ```php
  'profiler' => ['class' => \Magento\Framework\DB\Profiler::class, 'enabled' => true],
  ```
- On a full page cache hit the Request tab shows "page cache hit"; the other panels describe that cheap cached
  request, which has no layout rendering
- Add panels by implementing `Awesoft\Developer\Api\DebugBar\CollectorInterface` and adding it to the
  `collectors` argument of `Awesoft\Developer\Model\DebugBar\CollectorPool` in `di.xml`; a section can set
  `'formats' => [1 => 'sql']` to syntax-highlight a column, as the Database panel does
- The AJAX tab lists the page's AJAX/fetch requests (static and media assets are skipped). AJAX and other
  non-page responses, including REST and GraphQL when "Profile API Requests" is on, are profiled to
  `var/awesoft/debug_bar/` (newest 100 kept) and tagged with an `X-Awesoft-Debug-Token` header; "Inspect"
  loads that request's panels into the bar. The profiles are served by the storefront route
  `awesoft_debugbar/panel/index`, also when inspecting from the admin
- SQL is formatted one clause per line, with indented columns, conditions and subqueries; "Format SQL" in the
  panel header switches back to single-line queries

## CLI
```shell
bin/magento awesoft:developer:toggle <whoops|debugbar> <on|off>
```
