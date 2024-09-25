<?php

namespace App\Service;

use App\Util\Log;
use Elasticsearch\Client;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Elasticsearch\ClientBuilderFactory;
use Psr\Container\ContainerInterface;

class EsService
{
    protected string $index = '';
    protected Client|null $instance = null;

    #[inject]
    protected ContainerInterface $container;

    public function __construct($index)
    {
        $this->index = \Hyperf\Support\env('ES_PREFIX').$index;
    }

    public static function newEs($index): static
    {
        return new static($index);
    }

    public function getClient(): ?Client
    {
        if (!is_null($this->instance)) {
            return $this->instance;
        }

        try {
            $builder = $this->container->get(ClientBuilderFactory::class)->create();

            $esConf = \Hyperf\Config\config('es');
            if (empty($esConf['host'])) {
                throw new \Exception('host is empty');
            }

            $builder->setHosts($esConf['host']);

            if (!empty($esConf['api_id']) && !empty($esConf['api_key'])) {
                $builder->setApiKey($esConf['api_id'], $esConf['api_key']);
            }

            return $this->instance = $builder->build();
        } catch (\Exception $e) {
            Log::error('es getClient exception:'.$e->getMessage());
        }
        return null;
    }

    /**
     * create index
     * @param $body
     * @return array
     */
    public function createIndex($body): array
    {
        $params = [
            'index' => $this->index,
            'body'  => $body
        ];
        return $this->getClient()->indices()->create($params);
    }

    /**
     * delete index
     * @return array
     */
    public function deleteIndex(): array
    {
        $deleteParams = [
            'index' => $this->index,
        ];
        return $this->getClient()->indices()->delete($deleteParams);
    }

    /**
     * if exists
     * @return bool
     */
    public function exists(): bool
    {
        return $this->getClient()->indices()->exists(['index' => $this->index]);
    }

    /**
     * index a document
     * @param $body
     * @param $docId
     * @return callable|array
     */
    public function indexDocument($body, $docId = ''): callable|array
    {
        $params = [
            'index' => $this->index,
            'id' => $docId,
            'body'  => $body
        ];
        return $this->getClient()->index($params);
    }

    /**
     * update a document
     * @param $docId
     * @param $doc
     * @return callable|array
     */
    public function updateDocument($docId, $doc): callable|array
    {
        $params = [
            'index' => $this->index,
            'id' => $docId,
            'body' => [
                'doc' => $doc
            ]
        ];
        return $this->getClient()->update($params);
    }

    /**
     * delete a document
     * @param $docId
     * @return callable|array
     */
    public function deleteDocument($docId): callable|array
    {
        $params = [
            'index' => $this->index,
            'id' => $docId,
        ];
        return $this->getClient()->delete($params);
    }

    /**
     * get a document
     * @param $docId
     * @return callable|array
     */
    public function getDocument($docId): callable|array
    {
        $params = [
            'index' => $this->index,
            'id' => $docId,
        ];
        return $this->getClient()->get($params);
    }

    /**
     * get source
     * @param $docId
     * @return callable|array
     */
    public function getSource($docId): callable|array
    {
        $params = [
            'index' => $this->index,
            'id' => $docId,
        ];
        return $this->getClient()->getSource($params);
    }

    /**
     * search for a document
     * @param $body
     * @return callable|array
     */
    public function searchDocument($body): callable|array
    {
        $params = [
            'index' => $this->index,
            'body'  => $body
        ];
        return $this->getClient()->search($params);
    }

    public function getResponseSource($response): array
    {
        $data = [];
        foreach ($response['hits']['hits'] as $hit) {
            $data[] = $hit['_source'];
        }
        return $data;
    }

    public function __call($name, $arguments = [])
    {
        return $this->getClient()->{$name}(...$arguments);
    }
}