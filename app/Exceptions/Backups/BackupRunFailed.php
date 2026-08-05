<?php

namespace App\Exceptions\Backups;

use Exception;

/**
 * Raised after a failed run has already been recorded, so the failure also
 * surfaces as a failed job for operators reading the queue rather than the page.
 */
final class BackupRunFailed extends Exception {}
