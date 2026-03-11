<?php

declare(strict_types=1);

namespace App\Telegram\Middlewares;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Lowel\Telepath\Core\Router\Middleware\AbstractTelegramMiddleware;
use Lowel\Telepath\Exceptions\UpdateNotFoundInCurrentContextException;
use Lowel\Telepath\Exceptions\UserNotFoundInCurrentContextException;
use Lowel\Telepath\Facades\Extrasense;

class AuthMiddleware extends AbstractTelegramMiddleware
{
    public function handler(): callable
    {
        return static function (callable $callback) {
            try {
                /** @var User */
                $user = Auth::guard('telegram')->user();

                $tgUser = Extrasense::user();

                $user->update([
                    'first_name' => $tgUser->firstName,
                    'last_name' => $tgUser->lastName,
                    'username' => $tgUser->username,
                    'telegram_id' => $tgUser->id,
                    'is_bot' => $tgUser->isBot,
                ]);
            } catch (UserNotFoundInCurrentContextException|UpdateNotFoundInCurrentContextException $e) {
                $tgUser = Extrasense::user();

                User::create([
                    'first_name' => $tgUser->firstName,
                    'last_name' => $tgUser->lastName,
                    'username' => $tgUser->username,
                    'telegram_id' => $tgUser->id,
                    'is_bot' => $tgUser->isBot,
                ]);

                $user = Auth::guard('telegram')->user();
            }

            if (! $user->is_bot) {
                $callback();
            }
        };
    }
}
