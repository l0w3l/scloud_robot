<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Lowel\Telepath\Exceptions\UpdateNotFoundInCurrentContextException;
use Lowel\Telepath\Exceptions\UserNotFoundInCurrentContextException;
use Lowel\Telepath\Facades\Extrasense;

class TelegramGuard implements Guard
{
    protected Request $request;

    protected UserProvider $provider;

    protected ?Authenticatable $user = null;

    public function __construct(UserProvider $provider, Request $request)
    {
        $this->provider = $provider;
        $this->request = $request;
    }

    /**
     * @throws UserNotFoundInCurrentContextException
     * @throws UpdateNotFoundInCurrentContextException
     */
    public function user(): ?Authenticatable
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $telegramId = Extrasense::user()->id;

        if (! $telegramId) {
            return null;
        }

        $this->user = $this->provider->retrieveByCredentials([
            'telegram_id' => $telegramId,
        ]) ?? throw new UserNotFoundInCurrentContextException('User was not founded in current update context');

        return $this->user;
    }

    public function id(): int|string|null
    {
        return $this->user()?->getAuthIdentifier();
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return ! $this->check();
    }

    public function hasUser(): bool
    {
        return $this->user !== null;
    }

    public function validate(array $credentials = []): bool
    {
        return false;
    }

    public function setUser(Authenticatable $user): static
    {
        $this->user = $user;

        return $this;
    }
}
