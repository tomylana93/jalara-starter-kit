<?php

namespace App\Actions\Media;

use RuntimeException;

/**
 * Raised when an upload stopped being publishable while it was being processed.
 *
 * In practice this means its owner cancelled it after the worker had claimed it
 * but before the result was committed. It is thrown from inside the
 * finalization transaction purely so that transaction rolls back: the domain
 * change and the upload's own transition have to land together or not at all,
 * and this is what makes "not at all" happen.
 */
final class ImageUploadNoLongerPublishable extends RuntimeException {}
