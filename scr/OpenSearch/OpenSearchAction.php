<?php
namespace AdminShared\OpenSearch;


class OpenSearchAction implements OpenSearchActionInterface
{
    private Client $client;
    private OpenSearchUser $openSearchUser;

    /**
     * @throws GGException
     */
    public function __construct($eventType = EventType::Client)
    {
        $this->openSearchUser = $eventType == EventType::Client
            ? ApplicationSettings::getOpenSearchUser() : ApplicationSettings::getOpenSearchUserServer();

        try
        {
            $this->client  = (new ClientBuilder())
                ->setHosts(["http://{$this->openSearchUser->getHost()}:{$this->openSearchUser->getPort()}"])
                ->setBasicAuthentication($this->openSearchUser->getUser(), $this->openSearchUser->getPassword())
                ->setSSLVerification(false)
                ->build();
        }
        catch (Exception $exception)
        {
            AlarmMessages::sendAlarm(THREAD_EXCEPTIONS,
                'OpenSearch is not responding. The cluster is not running and/or is not available to process requests!',
                ['exception' => $exception]);
            return;
        }
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getOpenSearchUser(): OpenSearchUser
    {
        return $this->openSearchUser;
    }

    /**
     * @throws GGException
     */
    #[Performance]
    public function getEventById(string $eventId, string $index = 'events.*'): array
    {
        $result = [];

        $params = [
            'index'  => $index,
            'type'   => '_doc',
            'id'   => $eventId,
        ];

        try
        {
            $result = $this->client->get($params);
        }
        catch (Exception $exception)
        {
            $this->sendAlarmEvent($exception);
            return $result;
        }

        return $result;
    }

    /**
     * @throws GGException
     */
    public function getAccounts(array $accounts, string $index = 'accounts*', array $sort = []): array
    {
        $accounts = array_unique($accounts);

        $countAccounts = count($accounts);
        $accountsNew = [];

        foreach ($accounts as $account)
        {
            $accountsNew[] = (int)$account;
        }

        if ($countAccounts == 0)
        {
            return [];
        }

        $query['bool']['filter']['bool']['should']['terms']['Account'] = $accountsNew;

        return $this->getEvents($query, $index, $countAccounts, $sort);
    }

    /**
     * @throws GGException
     */
    public function getAccount(int $accountId): array
    {
        return $this->getEventById($accountId, 'accounts');
    }

    /**
     * @throws GGException
     */
    #[Performance]
    public function getEvents(array $query, string $index = 'events*', int $size = 100,
                              array $sort = ['@timestamp' => 'desc']): array
    {
        $result = [];
        $query = $this->setEnvironment($query);

        $params = [
            'index' => $index,
            'size' => $size,
            'body' => [
                'query' => $query,
                'sort' => $sort
            ]
        ];

        if ($query == null || (count($query) == 0))
        {
            $params = [
                'index' => $index,
                'size' => $size,
                'body' => [
                    'sort' => $sort
                ]
            ];
        }

        try
        {
            $result = $this->client->search($params);
        }
        catch (Exception $exception)
        {
            $this->sendAlarmEvent($exception);
            return $result;
        }

        return $result['hits']['hits'];
    }

    /**
     * @throws GGException
     */
    public function getCountEvents(array $query, string $index = 'events*'): int
    {
        $query = $this->setEnvironment($query);
        $result = 0;

        $params = [
            'index' => $index,
            'body' => [
                'query' => $query
            ]
        ];

        try
        {
            $result = $this->client->count($params);
        }
        catch (Exception $exception)
        {
            $this->sendAlarmEvent($exception);
            return $result;
        }

        return $result['count'];
    }

    private function setEnvironment($query): array
    {
        if ($this->openSearchUser->getEnv() === '')
        {
            return $query;
        }

        $countFilter = count($query['bool']['filter'] ?? []);

        if ($countFilter > 0)
        {
            $filters = $query['bool']['filter'];

            if (isset($filters['bool']))
            {
                unset($query['bool']['filter']['bool']);

                if (isset($filters['bool']['should']))
                {
                    $query['bool']['filter'][]['bool']['should'] = $filters['bool']['should'];
                }

                if (isset($filters['bool']['must_not']))
                {
                    $query['bool']['filter'][]['bool']['must_not'] = $filters['bool']['must_not'];
                }

                if (isset($filters['bool']['must']))
                {
                    $query['bool']['filter'][]['bool']['must'] = $filters['bool']['must'];
                }
            }
        }

        $countFilter = count($query['bool']['filter'] ?? []);
        $query['bool']['filter'][$countFilter]['bool']['must'][]['term']['env'] = $this->openSearchUser->getEnv();

        return $query;
    }

    /**
     * @throws GGException
     */
    private function sendAlarmEvent(Exception $exception): void
    {
        if ($exception instanceof Unauthorized401Exception)
        {
            AlarmMessages::sendAlarm(THREAD_EXCEPTIONS,
                'Failed to login to OpenSearch!', ['exception' => $exception]);
            return;
        }

        AlarmMessages::sendAlarm(THREAD_EXCEPTIONS,
            'Error receiving events!', ['exception' => $exception]);
    }
}