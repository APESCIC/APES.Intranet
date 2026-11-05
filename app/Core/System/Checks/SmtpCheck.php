<?php

namespace App\Core\System\Checks;

use RuntimeException;

/**
 * Connects to the SMTP server, reads its greeting and says QUIT. Never sends mail.
 */
class SmtpCheck implements AddonCheck
{
    private const TIMEOUT_SECONDS = 5;

    public function key(): string
    {
        return 'sendmail';
    }

    public function configured(): bool
    {
        return config('mail.default') === 'smtp' && filled(config('mail.mailers.smtp.host'));
    }

    public function probe(): void
    {
        $socket = @stream_socket_client(
            'tcp://'.config('mail.mailers.smtp.host').':'.(int) config('mail.mailers.smtp.port'),
            $errorCode,
            $errorMessage,
            self::TIMEOUT_SECONDS,
        );

        if ($socket === false) {
            throw new RuntimeException('SMTP connection failed.');
        }

        try {
            stream_set_timeout($socket, self::TIMEOUT_SECONDS);
            $greeting = (string) fgets($socket, 512);

            if (! str_starts_with($greeting, '220')) {
                throw new RuntimeException('SMTP server did not greet.');
            }

            fwrite($socket, "QUIT\r\n");
        } finally {
            fclose($socket);
        }
    }

    public function details(): array
    {
        return [
            'host' => (string) config('mail.mailers.smtp.host'),
            'port' => (string) config('mail.mailers.smtp.port'),
            'database' => null,
        ];
    }
}
