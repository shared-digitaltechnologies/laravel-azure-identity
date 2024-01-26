<?php

namespace Shrd\Laravel\Azure\MicrosoftGraphMailer;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredential;
use Shrd\Laravel\Azure\Identity\Exceptions\AzureCredentialException;
use Shrd\Laravel\Azure\Identity\Scopes\AzureScope;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\MessageConverter;

class MicrosoftGraphTransport extends AbstractTransport
{
    public const MICROSOFT_GRAPH_BASE_URL = 'https://graph.microsoft.com/v1.0';

    public function __construct(protected TokenCredential $credential,
                                protected ?string $user = null,
                                protected bool $saveToSentItems = false,
                                EventDispatcherInterface $dispatcher = null,
                                LoggerInterface $logger = null)
    {
        parent::__construct($dispatcher, $logger);
    }

    /**
     * @throws AzureCredentialException
     */
    protected function baseRequest(): PendingRequest
    {
        $token = $this->credential->token(AzureScope::microsoftGraph());
        return Http::withToken($token->accessToken, $token->tokenType ?? 'Bearer')
            ->baseUrl(self::MICROSOFT_GRAPH_BASE_URL);
    }

    protected function getSendEndpoint(?string $from): string
    {
        if($this->user !== null) return "/users/$this->user/sendMail";
        if($from !== null) return "/users/$from/sendMail";
        return "/me/sendMail";
    }

    /**
     * @throws RequestException
     * @throws AzureCredentialException
     */
    protected function sendMail(?string $from, array $payload): Response
    {
        return $this->baseRequest()
            ->post($this->getSendEndpoint($from), $payload)
            ->throw();
    }

    protected function getSaveToSentItems(): bool
    {
        return $this->saveToSentItems;
    }

    /**
     * @throws RequestException
     * @throws AzureCredentialException
     */
    protected function doSend(SentMessage $message): void
    {
        $originalMessage = $message->getOriginalMessage();
        assert($originalMessage instanceof Message);

        $email = MessageConverter::toEmail($originalMessage);
        $envelope = $message->getEnvelope();

        $html = $email->getHtmlBody();

        [$attachments, $html] = $this->prepareAttachments($email, $html);

        $payload = [
            "message" => [
                "subject" => $email->getSubject(),
                'body' => [
                    "contentType" => $html === null ? 'Text' : 'HTML',
                    "content" => $html !== null ? $html : $email->getTextBody()
                ],
                'toRecipients' => $this->transformEmailAddresses($this->getRecipients($email, $envelope)),
                'ccRecipients' => $this->transformEmailAddresses(collect($email->getCc())),
                'bccRecipients' => $this->transformEmailAddresses(collect($email->getBcc())),
                'replyTo' => $this->transformEmailAddresses(collect($email->getReplyTo())),
                'sender' => $this->transformEmailAddress($envelope->getSender()),
                'attachments' => $attachments
            ],
            'saveToSentItems' => $this->getSaveToSentItems()
        ];

        $this->sendMail($email->getSender()?->getAddress(), $payload);
    }

    protected function prepareAttachments(Email $email, ?string $html): array
    {
        $attachments = [];
        foreach ($email->getAttachments() as $attachment) {
            $headers = $attachment->getPreparedHeaders();
            $fileName = $headers->getHeaderParameter('Content-Disposition', 'filename');

            $attachments[] = [
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name' => $fileName,
                'contentType' => $attachment->getMediaType(),
                'contentBytes' => base64_encode($attachment->getBody()),
                'contentId' => $fileName,
                'isInline' => $headers->getHeaderBody('Content-Disposition') === 'inline'
            ];
        }

        return [$attachments, $html];
    }

    /**
     * @param Collection<array-key, Address> $recipients
     * @return array<array-key, array{emailAddress: array{ address: string }}>
     */
    protected function transformEmailAddresses(Collection $recipients): array
    {
        return $recipients
            ->map(fn(Address $recipient) => $this->transformEmailAddress($recipient))
            ->toArray();
    }

    /**
     * @return array{emailAddress: array{ address: string }}
     */
    protected function transformEmailAddress(Address $address): array
    {
        return [
            'emailAddress' => [
                'address' => $address->getAddress()
            ]
        ];
    }

    /**
     * @param Email $email
     * @param Envelope $envelope
     * @return Collection<array-key, Address>
     */
    protected function getRecipients(Email $email, Envelope $envelope): Collection
    {
        return collect($envelope->getRecipients())
            ->filter(fn (Address $address) => ! in_array($address, array_merge($email->getCc(), $email->getBcc()), true));
    }

    public function __toString(): string
    {
        return "MicrosoftGraphTransport[saveToSentItems=$this->saveToSentItems]";
    }
}
