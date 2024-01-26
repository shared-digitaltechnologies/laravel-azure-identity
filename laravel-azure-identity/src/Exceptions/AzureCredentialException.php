<?php

namespace Shrd\Laravel\Azure\Identity\Exceptions;

use Shrd\Laravel\Azure\Identity\Contracts\TokenCredentialDriver;
use Throwable;

/**
 * Interface for any exception that can be thrown by an TokenCredential.
 *
 * @see TokenCredentialDriver
 */
interface AzureCredentialException extends Throwable
{
}
