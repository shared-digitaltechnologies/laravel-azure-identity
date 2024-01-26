<?php

namespace Shrd\Laravel\Azure\MicrosoftGraphMailer\Tests\Feature;

use Shrd\Laravel\Azure\Identity\Credentials\SimpleTokenCredential;
use Shrd\Laravel\Azure\Identity\Drivers\TokenCredentialDriverFactory;
use Shrd\Laravel\Azure\MicrosoftGraphMailer\MicrosoftGraphTransport;
use Shrd\Laravel\Azure\MicrosoftGraphMailer\Tests\TestCase;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MicrosoftGraphTransportTest extends TestCase
{
    /**
     * @throws TransportExceptionInterface
     */
    public function test_send_simple_mail()
    {

        $instance = new MicrosoftGraphTransport(
            credential: new SimpleTokenCredential(TokenCredentialDriverFactory::instance()->fromEnv()),
            saveToSentItems: false
        );

        $email = (new Email)
            ->sender(new Address('precontools@shared.nl'))
            ->from('precontools@shared.nl')
            ->to('roel@shared.nl')
            ->subject('Simple Test Email')
            ->text('Simple test email body')
            ->attach("HALLO HALLO", 'test.txt');

        $instance->send($email);
    }
}
