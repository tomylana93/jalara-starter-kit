<?php

namespace App\Jobs\Media;

/**
 * Queue identity for a documentation editor image.
 *
 * The workflow lives in `App\Actions\Media\ProcessQueuedImageUpload`; this
 * class exists so documentation uploads are their own nameable job.
 */
class ProcessDocumentationImageUpload extends ProcessQueuedImageUpload {}
