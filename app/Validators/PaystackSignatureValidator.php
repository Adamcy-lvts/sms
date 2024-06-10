<?php

// app/Validators/PaystackSignatureValidator.php

namespace App\Validators;

use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\Exceptions\InvalidConfig;
use Spatie\WebhookClient\Exceptions\WebhookFailed;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;

class PaystackSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $signature = $request->header($config->signatureHeaderName);

        if ($signature !== hash_hmac('sha512', $request->getContent(), $config->signingSecret)) {
            throw InvalidConfig::signingSecretNotSet();
        }

        return true;
    }
}
