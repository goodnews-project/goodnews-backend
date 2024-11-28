<?php

namespace App\Protocol\At;

use App\Exception\PdsException;
use App\Protocol\At\Key\Did;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use ParagonIE\ConstantTime\Base32;
use function Hyperf\Support\env;

class Plc
{
    #[Inject]
    public ClientFactory $clientFactory;

    const PLC_HOST = 'https://plc.directory';
    public function getDocument($did)
    {
        $req = $this->clientFactory->create()->get(self::PLC_HOST.'/'.$did);
        $content = $req->getBody()->getContents();
        return json_decode($content, true);
    }

    public function createDID($handle)
    {
        $SigningKey = Did::generate(Did::KEY_TYPE_SECP256K1)->getDID();
        $recoveryKey = Did::generate(Did::KEY_TYPE_SECP256K1)->getDID();
        $this->validateHandle($handle);
        $op = [
            'type' => 'create',
            'signingKey' => $SigningKey,
            'recoveryKey' => $recoveryKey,
            'handle' => $handle,
            'service' => 'https://'.env('PDS_DOMAIN'),
            'prev' => null
        ];
        $sig = base64_encode(json_encode($op));
        $op['sig'] = $sig;
        $opdid = $this->didForCreateOp($op);
        $req = $this->clientFactory->create()->post(self::PLC_HOST.'/'.$opdid, ['json' => $op]);
        $content = $req->getBody()->getContents();
        return json_decode($content, true);
    }

    public function didForCreateOp($op)
    {
        $buf = json_encode($op);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new PdsException(json_last_error_msg());
        }

        $h = hash('sha256', $buf, true);
        $enchash = Base32::encode($h);
        $enchash = strtolower($enchash);

        return 'did:plc:' . substr($enchash, 0, 24);
    }

    private function validateHandle($handle)
    {
        if (empty($handle)) {
            new PdsException('expected handle, got empty string');
        }

        if (strlen($handle) > 253) {
            new PdsException('handle is too long (253 chars max)');
        }

        if (!preg_match('/^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/', $handle)) {
            new PdsException(sprintf("handle syntax didn't validate via regex: %s", $handle));
        }
    }

}