<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;
use Throwable;

class MailtrapEmailService
{
    public function sendVerificationEmail(User $user, string $verificationUrl): bool
    {
        $token = (string) config('services.mailtrap.token');
        $fromEmail = $this->fromEmail();

        if ($token !== '' && $fromEmail !== '') {
            return $this->sendViaApi($user, $verificationUrl, $token, $fromEmail);
        }

        if ($this->canSendViaMailer($fromEmail)) {
            return $this->sendViaMailer($user, $verificationUrl, $fromEmail);
        }

        Log::warning('Verification email skipped because mail configuration is missing.', [
            'user_id' => $user->id,
        ]);

        return false;
    }

    private function sendViaApi(User $user, string $verificationUrl, string $token, string $fromEmail): bool
    {
        $payload = [
            'from' => [
                'email' => $fromEmail,
                'name' => $this->fromName(),
            ],
            'to' => [
                ['email' => $user->email],
            ],
            'subject' => 'Verify your email for STrack',
            'text' => $this->buildTextBody($user, $verificationUrl),
            'html' => $this->buildHtmlBody($user, $verificationUrl),
            'category' => (string) config('services.mailtrap.category', 'account-verification'),
        ];

        try {
            $response = Http::timeout(10)
                ->withToken($token)
                ->acceptJson()
                ->post('https://send.api.mailtrap.io/api/send', $payload);

            if ($response->successful()) {
                return true;
            }

            Log::warning('Mailtrap API verification email request failed.', [
                'user_id' => $user->id,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Mailtrap API verification email threw an exception.', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);
        }

        return false;
    }

    private function sendViaMailer(User $user, string $verificationUrl, string $fromEmail): bool
    {
        try {
            $html = $this->buildHtmlBody($user, $verificationUrl);
            $text = $this->buildTextBody($user, $verificationUrl);
            $fromName = $this->fromName();

            Mail::mailer(config('mail.default'))
                ->html($html, function ($message) use ($user, $fromEmail, $fromName, $text): void {
                    $message
                        ->to($user->email)
                        ->from($fromEmail, $fromName)
                        ->subject('Verify your email for STrack');

                    $symfonyMessage = $message->getSymfonyMessage();

                    if ($symfonyMessage instanceof Email) {
                        $symfonyMessage->text($text);
                    }
                });

            return true;
        } catch (Throwable $exception) {
            Log::error('Mailer verification email threw an exception.', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);
        }

        return false;
    }

    private function canSendViaMailer(string $fromEmail): bool
    {
        return $fromEmail !== '' && (string) config('mail.default') !== '';
    }

    private function fromEmail(): string
    {
        return (string) (config('services.mailtrap.sender_email') ?: config('mail.from.address') ?: '');
    }

    private function fromName(): string
    {
        return (string) (config('services.mailtrap.sender_name') ?: config('mail.from.name') ?: config('app.name'));
    }

    private function buildTextBody(User $user, string $verificationUrl): string
    {
        return implode("\n\n", [
            "Hi {$user->login},",
            'Thanks for registering in STrack. Confirm your email address to activate your account.',
            $verificationUrl,
            'This link expires in 60 minutes.',
        ]);
    }

    private function buildHtmlBody(User $user, string $verificationUrl): string
    {
        $login = e($user->login);
        $url = e($verificationUrl);

        return <<<HTML
<div style="font-family:Arial,sans-serif;line-height:1.6;color:#191714">
  <p>Hi {$login},</p>
  <p>Thanks for registering in STrack. Confirm your email address to activate your account.</p>
  <p>
    <a href="{$url}" style="display:inline-block;padding:12px 18px;border-radius:999px;background:#bf2323;color:#ffffff;text-decoration:none;font-weight:700">
      Verify email
    </a>
  </p>
  <p>If the button does not work, open this link:</p>
  <p><a href="{$url}">{$url}</a></p>
  <p>This link expires in 60 minutes.</p>
</div>
HTML;
    }
}
