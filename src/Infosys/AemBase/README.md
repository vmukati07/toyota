# Infosys_AemBase

This module contains logic to facilitate the integration with AEM.

## Sitemap

This module provides a preference for `Magneto\Sitemap\Model\Sitemap` (`Infosys_AemBase\Rewrite\Magento\Sitemap\Model\Sitemap`);
 this is not ideal, but the Sitemap is part of core that hasn't been fully updated to modern best practices.

The primary modification to the Sitemap functionality is ensuring that Sitemap generation takes into account the
configured AEM settings, so that the sitemap:

- Has correct links
- Also generates a gzipped version of each non-index sitemap file, since the sitemap files end up being ~10MB each.

Before using Sitemap functionality, each store needs to have it's AEM configuration setup; this configuration can be
found at: Stores > Configuration > Adobe Experience Manager > General. The following need to be set, per-store:

- AEM Publish Domain

The Sitemap additions include 3 new Console Commands:
- infosys:aem:show-missing-sitemaps (`ShowMissingSitemaps`)
- infosys:aem:generate-sitemaps (`GenerateSitemaps`)
- infosys:aembase:show-missing-domains (`ShowMissingAemPublishDomains`)

### ShowMissingSitemaps
Renders a table displaying Stores that do not have a Sitemap configured

### GenerateSitemaps
Asks to provide a sitemap filename and a file path.

Generates a Sitemap entry for each Store without one, of the form `{store_code}_{sitemap filename}.xml` with the given\
file path.

### ShowMissingAemPublishDomains
The default scope for config path `aem_general_config/general/aem_domain` should not be present.

Renders a table showing all of the store ids that are equal to the default scope, which is a good indicator that the
store needs to have it's configuration for `aem_general_config/general/aem_domain` set.

Added email template system variable into di.xml file to get AEM url path and directly to RMA customer comment Email templates.