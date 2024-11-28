<?php
namespace App\Protocol\At;

use App\Exception\PdsException;
use GuzzleHttp\Client;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use function Hyperf\Support\env;

class Pds
{

    const PLC_RESOLVE_HANDLE_URL = 'https://plc.directory/xrpc/com.atproto.identity.resolveHandle?handle=%s';
    const BSKY_RESOLVE_HANDLE_URL = 'https://public.api.bsky.app/xrpc/com.atproto.identity.resolveHandle?handle=%s';
    const XRPC_ATPROTO_PROFILE = '/xrpc/app.bsky.actor.getProfile';
    const XRPC_ATPROTO_DESCRIBE_SERVER = '/xrpc/com.atproto.server.describeServer';


    public Client $client;
    #[Inject]
    protected Plc $plc;

    public function __construct(public ClientFactory $clientFactory)
    {
        $this->client = $this->clientFactory->create();
    }

    public function resolveServiceEndpointDid($did)
    {
        $did = $this->resolveAuthorDID($did);
        $doc = $this->plc->getDocument($did);
        return $doc['service'][0]['serviceEndpoint'];
    }

    public function getProfile($author)
    {
        $serviceEndpoint = $this->resolveServiceEndpointDid($author);
        $res = $this->client->get($serviceEndpoint.'/'.self::XRPC_ATPROTO_PROFILE);
        return json_decode($res, true);
    }

    public function resolveAuthorDID($author)
    {
        if (str_starts_with($author, 'did:')) {
            return $author;
        }

        $response = $this->client->get(sprintf(self::PLC_RESOLVE_HANDLE_URL, $author));
        $data = json_decode($response, true);

        return $data['did'] ?? null;
    }

    public function serverDescribeServer()
    {

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

    protected function generateDid($publicKey)
    {

    }



}