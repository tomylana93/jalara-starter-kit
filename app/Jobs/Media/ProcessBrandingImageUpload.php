<?php

namespace App\Jobs\Media;

/**
 * Publishes a processed branding image into the application settings.
 *
 * Branding is a shared, application-wide slot, so the permission that opened
 * the upload is checked again here: an administrator whose access was revoked
 * while the image sat in the queue must not still be able to change the logo.
 */
class ProcessBrandingImageUpload extends ProcessQueuedImageUpload {}
