<?php
namespace App\Protocol\At;

use App\Exception\PdsException;
use GuzzleHttp\Client;
use Hyperf\Guzzle\ClientFactory;
use function Hyperf\Support\env;

class Pds
{
    const CREATE_DID_URL = 'https://plc.directory/xrpc/com.atproto.identity.createDID';
    const UPDATE_DID_URL = 'https://plc.directory/xrpc/com.atproto.identity.updateDID';
    const PLC_RESOLVE_HANDLE_URL = 'https://plc.directory/xrpc/com.atproto.identity.resolveHandle?handle=%s';

    public Client $client;
    public function __construct(public ClientFactory $clientFactory)
    {
        $this->client = $this->clientFactory->create();
    }

    public function createDID($username, $publicKey)
    {
        try {
            $res = $this->client->post(self::CREATE_DID_URL, ['json' => ['handle' => $this->generateHandle($username), 'publicKey' => $publicKey]]);
            $content = $res->getBody()->getContents();
            return json_decode($content, true);
        } catch (\Exception $e) {
            throw new PdsException($e->getMessage());
        }

    }

    public function updateDID($did)
    {
        try {
            $res = $this->client->post(self::UPDATE_DID_URL, ['json' => ['did' => $did, 'serviceEndpoint' => 'https://'.env('PDS_DOMAIN')]]);
            $content = $res->getBody()->getContents();
            return json_decode($content, true);
        } catch (\Exception $e) {
            throw new PdsException($e->getMessage());
        }
    }

    public function resolveAuthorDID($author)
    {
        if (str_starts_with($author, 'did:')) {
            return $author;
        }

        $response = file_get_contents(sprintf(self::PLC_RESOLVE_HANDLE_URL, $author));
        $data = json_decode($response, true);

        return $data['did'] ?? null;
    }

    protected function generateHandle($username)
    {
        $cleanUsername = preg_replace('/[^a-z0-9-]/', '', strtolower($username));
        $cleanUsername = preg_replace('/^-+|-+$/', '', $cleanUsername);
        if (empty($cleanUsername)) {
            throw new PdsException("Invalid username provided.");
        }

        return $cleanUsername.env('PDS_DOMAIN');
    }

}