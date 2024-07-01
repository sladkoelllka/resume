<?php

namespace AdminShared\OpenSearch;

use OpenSearch\Client;

interface OpenSearchActionInterface
{
    public function getClient(): Client;

    public function getOpenSearchUser(): OpenSearchUser;

    public function getEventById(string $eventId, string $index): array;

    public function getAccounts(array $accounts, string $index = '', array $sort = []): array;

    public function getAccount(int $accountId): array;

    public function getEvents(array $query, string $index, int $size = 100, array $sort = ['@timestamp' => 'desc']): array;

    public function getCountEvents(array $query, string $index): int;
}