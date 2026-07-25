<?php

/**
 * AK Workforce Pro — root fallback front controller.
 *
 * PREFERRED setup: point the domain's document root at the /public folder,
 * OR let the bundled root .htaccess forward requests into /public.
 *
 * This file only runs when the document root is the PROJECT ROOT and
 * mod_rewrite is unavailable, so the site still boots instead of showing a
 * "Forbidden" / directory-index error. For correct asset & upload URLs,
 * using /public as the document root is still recommended.
 */

require __DIR__.'/public/index.php';
