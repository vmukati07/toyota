# Infosys_RollingSitemap

This module replaces the standard sitemap generation provided by `Magento_Sitemap`.

While enabled, instead of sitemap generation for all sitemaps occurring according to `frequency` and `time`, sitemap
generation for only the oldest sitemap will occur at a frequency defined by a new adminhtml configuration;
`sitemap/generate/rolling_cron_expr`.

Both `sitemap/generate/frequency` and `sitemap/generate/time` are suppressed from the Sitemap configuration.

`sitemap/generate/rolling_cron_expr` is shown in their place.

`Magento_Sitemap` does not expose a `SitemapInterface` for `Sitemap`, so Magento's `Observer` is preferenced to this
module's `Observer`.

A warning is provided on the Site Map index page to inform the user that this module is enabled, along with a link to
the Sitemap configuration.

When a rolling sitemap generation starts, completes, or fails, a log entry is created in
`var/log/Infosys_RollingSitemap`.

Configuration has been inserted into the standard sitemap configuration "Generation Settings" section at
Stores > Configuration > Catalog > Xml Sitemap, under the "default" scope. This field is validated by a new validator
introduced in this module: `validate-cron-expr`; see `view/adminhtml/requirejs-config.js` and
`view/adminhtml/web/js/admin-config/cron-expr-validator-rule-mixin.js` for information.

`crontab.xml` in this module is modifying the `sitemap_generate` cron job, by providing it with a config schedule which
is (also introduced by this module). 