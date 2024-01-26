<?php

namespace Shrd\Laravel\Azure\Identity\Tokens;

use ArrayAccess;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Exception;
use Illuminate\Contracts\Support\Jsonable;
use JetBrains\PhpStorm\NoReturn;
use JsonSerializable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Safe\Exceptions\JsonException;
use Safe\Exceptions\UrlException;
use Shrd\Laravel\Azure\Identity\Exceptions\InvalidAccessTokenJsonException;
use stdClass;

/**
 * Represents an access token for an azure service.
 *
 * @property-read stdClass $header
 * @property-read stdClass $body
 * @property-read bool $expired
 * @property-read bool $expires
 * @property-read CarbonInterval|null $expiresIn
 * @property-read string $authorization
 * @property-read string $aud
 * @property-read string $typ
 * @property-read string $alg
 * @property-read ?string $x5t
 * @property-read ?string $kid
 * @property-read ?string $iss
 * @property-read ?int $iat
 * @property-read ?int $nbf
 * @property-read ?string $acr
 * @property-read ?array<string> $groups
 * @property-read ?string $oid
 * @property-read ?string $name
 * @property-read ?string $family_name
 * @property-read ?string $given_name
 * @property-read ?string $unique_name
 * @property-read ?string $upn
 * @property-read ?string $sub
 * @property-read ?string $ipaddr
 * @property-read ?string $tid
 * @property-read ?string $appid
 * @property-read ?string $ver
 */
readonly class AccessToken implements JsonSerializable, Jsonable
{
    public ?Carbon $expiresOn;

    public function __construct(public string $accessToken,
                                mixed $expiresOn = null,
                                public ?string $subscription = null,
                                public ?string $tenant = null,
                                public ?string $tokenType = null)
    {
        if(is_numeric($expiresOn)) {
            $this->expiresOn = Carbon::createFromTimestamp($expiresOn);
        } elseif($expiresOn !== null) {
            $this->expiresOn = Carbon::make($expiresOn);
        } else {
            $this->expiresOn = null;
        }
    }

    /**
     * @throws UrlException
     * @throws JsonException
     */
    private function decodeTokenPart(string $base64UrlEncoded): stdClass
    {
        return \Safe\json_decode(
            \Safe\base64_decode(
                str_replace(
                    ['-', '_'],
                    ['+', '/'],
                    $base64UrlEncoded
                )
            )
        );
    }

    /**
     * @return array{stdClass, stdClass}
     *@throws JsonException
     * @throws UrlException
     */
    private function decodeToken(): array
    {
        [$encodedHead, $encodedBody] = explode('.', $this->accessToken);
        return [$this->decodeTokenPart($encodedHead), $this->decodeTokenPart($encodedBody)];
    }

    /**
     * @throws UrlException
     * @throws JsonException
     */
    public function getTokenHeader(): stdClass
    {
        [$header,] = $this->decodeToken();
        return $header;
    }

    /**
     * @throws UrlException
     * @throws JsonException
     */
    public function getTokenBody(): stdClass
    {
        [,$body] = $this->decodeToken();
        return $body;
    }

    public static function fromResponseObj(stdClass $result): self
    {
        return self::fromResponseArray((array)$result);
    }

    protected static function expiresOnFromResult(array|ArrayAccess $result): mixed
    {
        $on = $result['expiresOn']
            ?? $result['expires_on']
            ?? $result['expiresAt']
            ?? $result['expires_at']
            ?? null;

        if($on !== null) return $on;

        $in = $result['expires_in'] ?? $result['expiresIn'] ?? null;
        if($in !== null) return Carbon::now()->add('seconds', $in);

        return null;
    }

    public static function fromResponseArray(array|ArrayAccess $result): self
    {
        return new self(
            accessToken: $result['accessToken'] ?? $result['access_token'] ?? $result['token'],
            expiresOn: self::expiresOnFromResult($result),
            subscription: $result['subscription'] ?? null,
            tenant: $result['tenant'] ?? null,
            tokenType: $result['tokenType'] ?? $result['token_type'] ?? null
        );
    }

    /**
     * @throws InvalidAccessTokenJsonException
     */
    public static function fromJsonString(string $jsonString): self
    {
        try {
            $result = \Safe\json_decode($jsonString, true);
        } catch (JsonException $exception) {
            throw new InvalidAccessTokenJsonException($exception, $jsonString);
        }

        return self::fromResponseArray($result);
    }

    public static function fromString(string $value): self
    {
        try {
            return self::fromJsonString($value);
        } catch (InvalidAccessTokenJsonException) {
            return new self($value);
        }
    }

    public static function from(mixed $value): self
    {
        if($value instanceof self) return $value;

        if($value instanceof ResponseInterface) $value = $value->getBody();
        if($value instanceof StreamInterface) return self::fromString($value->getContents());
        if(is_string($value)) return self::fromString($value);
        if(is_array($value) || $value instanceof ArrayAccess) {
            return self::fromResponseArray($value);
        }

        return self::fromResponseObj($value);
    }

    public function isExpired(mixed $at = null): bool
    {
        if($this->expiresOn === null) return false;
        if($at === null) return $this->expiresOn->isPast();
        $at = Carbon::make($at) ?? Carbon::now();
        return $at->isBefore($this->expiresOn);
    }

    public function getExpires(): bool
    {
        return $this->expiresOn !== null;
    }

    public function getAuthorizationHeader(): string
    {
        $tokenType = $this->tokenType ?? 'Bearer';
        return "$tokenType $this->accessToken";
    }

    public function getExpiresIn(mixed $at = null): CarbonInterval|null
    {
        return $this->expiresOn?->diffAsCarbonInterval($at, absolute: false)?->times(-1);
    }

    /**
     * Gives the amount of time that this access token may be stored in cache.
     *
     * @param mixed $leeway
     * @param mixed $default
     * @param mixed $at
     * @return CarbonInterval
     */
    public function getCacheLifetime(mixed $leeway = null, mixed $default = null, mixed $at = null): CarbonInterval
    {
        $expiresIn = $this->getExpiresIn($at) ?? CarbonInterval::make($default) ?? CarbonInterval::hour();
        $leeway    = CarbonInterval::make($leeway) ?? CarbonInterval::minutes(5);
        return $expiresIn->sub($leeway);
    }

    public function toString(): string
    {
        return $this->accessToken;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @throws UrlException
     * @throws JsonException
     */
    private function getDecodedValue(string $key): mixed
    {
        [$body, $header] = $this->decodeToken();
        if(isset($body->$key)) return $body->$key;
        if(isset($header->$key)) return $header->$key;
        return null;
    }

    /**
     * @throws UrlException
     * @throws JsonException
     */
    private function decodedValueExists(string $key): bool
    {
        [$body, $header] = $this->decodeToken();
        if(isset($body->$key)) return true;
        if(isset($header->$key)) return true;
        return false;
    }

    /**
     * @throws UrlException
     * @throws JsonException
     */
    public function __get(string $name)
    {
        return match ($name) {
            'header' => $this->getTokenHeader(),
            'body' => $this->getTokenBody(),
            'expired' => $this->isExpired(),
            'expires' => $this->getExpires(),
            'expiresIn' => $this->getExpiresIn(),
            'authorization' => $this->getAuthorizationHeader(),
            default => $this->getDecodedValue($name),
        };
    }

    /**
     * @throws UrlException
     * @throws JsonException
     */
    public function __isset(string $name)
    {
        return match ($name) {
            'header', 'body', 'expired', 'expires', 'authorization' => true,
            'expiresIn' => $this->getExpires(),
            default => $this->decodedValueExists($name),
        };
    }

    /**
     * @throws Exception
     */
    #[NoReturn] public function dd(): static
    {
        /**
         * @psalm-suppress ForbiddenCode
         */
        dd($this->__debugInfo());
    }

    /**
     * @throws Exception
     */
    public function __debugInfo(): ?array
    {
        return [
            "expiresOn" => $this->expiresOn?->format("H:i:s d-m-Y"),
            "expiresIn" => $this->expiresIn?->forHumans(),
            "header" => $this->getTokenHeader(),
            "body" => $this->getTokenBody(),
            "subscription" => $this->subscription,
            "tenant" => $this->tenant,
            "tokenType" => $this->tokenType
        ];
    }

    public function toArray(): array
    {
        return [
            "access_token" => $this->accessToken,
            "expires_on"   => $this->expiresOn?->getTimestamp(),
            "tenant"       => $this->tenant,
            "subscription" => $this->subscription,
            "token_type"   => $this->tokenType,
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
