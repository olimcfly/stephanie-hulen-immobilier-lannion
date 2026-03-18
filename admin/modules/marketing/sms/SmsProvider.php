<?php
/**
 * SMS PROVIDER — Multi-driver
 * /admin/modules/marketing/sms/SmsProvider.php
 * Drivers : Brevo · Twilio · OVH · (extensible)
 */

// ══════════════════════════════════════════════════════════════════
// INTERFACE
// ══════════════════════════════════════════════════════════════════
interface SmsProviderInterface {
    /** Envoie un SMS, retourne ['success'=>bool, 'message_id'=>string|null, 'error'=>string|null, 'cost'=>float|null] */
    public function send(string $to, string $message, string $sender = ''): array;

    /** Vérifie que les credentials sont valides, retourne ['valid'=>bool, 'balance'=>float|null, 'error'=>string|null] */
    public function testConnection(): array;

    /** Nom lisible du provider */
    public function getName(): string;

    /** Retourne le solde disponible ou null si non supporté */
    public function getBalance(): ?float;
}

// ══════════════════════════════════════════════════════════════════
// DRIVER BREVO (ex-Sendinblue)
// ══════════════════════════════════════════════════════════════════
class BrevoSmsDriver implements SmsProviderInterface {

    private string $apiKey;
    private string $baseUrl = 'https://api.brevo.com/v3';

    public function __construct(string $apiKey) {
        $this->apiKey = $apiKey;
    }

    public function getName(): string { return 'Brevo'; }

    public function send(string $to, string $message, string $sender = 'IMMOLOCAL'): array {
        $to = $this->normalizePhone($to);
        if (!$to) return ['success'=>false,'message_id'=>null,'error'=>'Numéro invalide','cost'=>null];

        $payload = [
            'type'    => 'transactional',
            'unicodeEnabled' => false,
            'sender'  => $sender ?: 'IMMOLOCAL',
            'recipient' => $to,
            'content' => $message,
        ];

        $response = $this->request('POST', '/transactionalSMS/sms', $payload);

        if ($response['status'] === 201 || $response['status'] === 200) {
            return [
                'success'    => true,
                'message_id' => $response['body']['messageId'] ?? null,
                'error'      => null,
                'cost'       => $response['body']['usedCredits'] ?? null,
            ];
        }
        return [
            'success'    => false,
            'message_id' => null,
            'error'      => $response['body']['message'] ?? 'Erreur Brevo (HTTP '.$response['status'].')',
            'cost'       => null,
        ];
    }

    public function testConnection(): array {
        $response = $this->request('GET', '/account');
        if ($response['status'] === 200) {
            $credits = $response['body']['plan'][0]['credits'] ?? null;
            return ['valid'=>true, 'balance'=>$credits, 'error'=>null];
        }
        return ['valid'=>false, 'balance'=>null, 'error'=>$response['body']['message'] ?? 'Clé API invalide'];
    }

    public function getBalance(): ?float {
        $response = $this->request('GET', '/account');
        if ($response['status'] === 200) {
            foreach ($response['body']['plan'] ?? [] as $plan) {
                if (isset($plan['credits'])) return (float)$plan['credits'];
            }
        }
        return null;
    }

    private function normalizePhone(string $phone): string {
        $phone = preg_replace('/\D/', '', $phone);
        if (strlen($phone) === 10 && $phone[0] === '0') {
            $phone = '33' . substr($phone, 1);
        }
        if (strlen($phone) > 0 && $phone[0] !== '+') {
            $phone = '+' . $phone;
        }
        return strlen($phone) >= 10 ? $phone : '';
    }

    private function request(string $method, string $endpoint, array $body = []): array {
        $url = $this->baseUrl . $endpoint;
        $ch  = curl_init($url);
        $headers = [
            'accept: application/json',
            'content-type: application/json',
            'api-key: ' . $this->apiKey,
        ];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        $raw    = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);
        if ($err) return ['status'=>0,'body'=>['message'=>'cURL: '.$err]];
        return ['status'=>$status, 'body'=>json_decode($raw, true) ?? []];
    }
}

// ══════════════════════════════════════════════════════════════════
// DRIVER TWILIO
// ══════════════════════════════════════════════════════════════════
class TwilioSmsDriver implements SmsProviderInterface {

    private string $accountSid;
    private string $authToken;
    private string $fromNumber;
    private string $baseUrl = 'https://api.twilio.com/2010-04-01';

    public function __construct(string $accountSid, string $authToken, string $fromNumber) {
        $this->accountSid = $accountSid;
        $this->authToken  = $authToken;
        $this->fromNumber = $fromNumber;
    }

    public function getName(): string { return 'Twilio'; }

    public function send(string $to, string $message, string $sender = ''): array {
        $to = $this->normalizePhone($to);
        if (!$to) return ['success'=>false,'message_id'=>null,'error'=>'Numéro invalide','cost'=>null];

        $url     = "{$this->baseUrl}/Accounts/{$this->accountSid}/Messages.json";
        $payload = [
            'From' => $this->fromNumber,
            'To'   => $to,
            'Body' => $message,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($payload),
            CURLOPT_USERPWD        => $this->accountSid . ':' . $this->authToken,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw    = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($err) return ['success'=>false,'message_id'=>null,'error'=>'cURL: '.$err,'cost'=>null];

        $body = json_decode($raw, true) ?? [];
        if ($status === 201) {
            return [
                'success'    => true,
                'message_id' => $body['sid'] ?? null,
                'error'      => null,
                'cost'       => isset($body['price']) ? abs((float)$body['price']) : null,
            ];
        }
        return [
            'success'    => false,
            'message_id' => null,
            'error'      => $body['message'] ?? 'Erreur Twilio (HTTP '.$status.')',
            'cost'       => null,
        ];
    }

    public function testConnection(): array {
        $url = "{$this->baseUrl}/Accounts/{$this->accountSid}.json";
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $this->accountSid . ':' . $this->authToken,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $raw    = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $body = json_decode($raw, true) ?? [];
        if ($status === 200) return ['valid'=>true,'balance'=>null,'error'=>null];
        return ['valid'=>false,'balance'=>null,'error'=>$body['message'] ?? 'Credentials invalides'];
    }

    public function getBalance(): ?float { return null; } // Twilio ne donne pas le solde simplement

    private function normalizePhone(string $phone): string {
        $phone = preg_replace('/\D/', '', $phone);
        if (strlen($phone) === 10 && $phone[0] === '0') {
            $phone = '33' . substr($phone, 1);
        }
        return strlen($phone) >= 10 ? '+' . ltrim($phone, '+') : '';
    }
}

// ══════════════════════════════════════════════════════════════════
// FACTORY — instancie le bon driver depuis la config DB
// ══════════════════════════════════════════════════════════════════
class SmsProviderFactory {

    /**
     * Retourne une instance de driver à partir des settings DB
     * @param PDO $db
     * @return SmsProviderInterface|null
     */
    public static function fromDatabase(PDO $db): ?SmsProviderInterface {
        try {
            $rows  = $db->query("SELECT setting_key, setting_value FROM sms_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            return null;
        }

        $provider = $rows['provider'] ?? 'brevo';

        return match($provider) {
            'brevo'  => !empty($rows['brevo_api_key'])
                            ? new BrevoSmsDriver($rows['brevo_api_key'])
                            : null,
            'twilio' => (!empty($rows['twilio_account_sid']) && !empty($rows['twilio_auth_token']) && !empty($rows['twilio_from']))
                            ? new TwilioSmsDriver($rows['twilio_account_sid'], $rows['twilio_auth_token'], $rows['twilio_from'])
                            : null,
            default  => null,
        };
    }

    /** Liste des providers supportés */
    public static function supportedProviders(): array {
        return [
            'brevo'  => ['label'=>'Brevo (ex-Sendinblue)', 'icon'=>'fa-envelope-open-text', 'color'=>'#0092ff'],
            'twilio' => ['label'=>'Twilio',                 'icon'=>'fa-phone',              'color'=>'#f22f46'],
        ];
    }
}