<?php

namespace App\Jobs\Media;

/**
 * Publishes a processed avatar onto the account that uploaded it.
 *
 * The previous avatar keeps serving right up until the new path is saved, so a
 * failed or cancelled replacement leaves the account looking exactly as it did.
 */
class ProcessAvatarImageUpload extends ProcessQueuedImageUpload {}
