<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A backup could not be completed. The message is written for the logs and the
 * console, so it never contains transaction data or credentials.
 */
class BackupException extends RuntimeException {}
