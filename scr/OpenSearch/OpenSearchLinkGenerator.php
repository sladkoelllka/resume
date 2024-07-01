<?php

namespace AdminShared\OpenSearch;

use AdminShared\App\Actions\Services\Connection\ConnectionService;
use AdminShared\App\Actions\Utility\UtilityBase;

class OpenSearchLinkGenerator
{
    private static array $queryParams = ['_g' => [], '_a' => []];

    public static function setGParameter(string $key, $value): void
    {
        self::$queryParams['_g'][$key] = $value;
    }

    public static function setFilters(array $filters): void
    {
        self::setGParameter('filters', $filters);
    }

    public static function setColumns(array $columns): void
    {
        self::$queryParams['_a']['columns'] = array_map(fn($item) => $item, $columns);
    }

    public static function setSort(array $sort): void
    {
        self::$queryParams['_a']['sort'] = array_map(fn($item) => "'$item'", $sort);
    }

    public static function setTimeRange(string $from, string $to): void
    {
        $from = self::getDate($from);
        $to = self::getDate($to);
        self::$queryParams['_g']['time'] = (object)['from' => "$from", 'to' => "$to"];
    }

    public static function setRefreshInterval(int $interval): void
    {
        self::$queryParams['_g']['refreshInterval'] = (object)['pause' => '!f', 'value' => $interval];
    }

    public static function setIndex(string $index): void
    {
        self::$queryParams['_a']['index'] = "'$index'";
    }

    public static function setAFilters(array $filters, string $index): void
    {
        self::$queryParams['_a']['filters'] = self::buildFilters($filters, $index);
    }

    public static function setQuery(): void
    {
        self::$queryParams['_a']['query'] = (object)['language' => 'kuery', 'query' => "''"];
    }

    public static function generate(
        string $index = 'events.*',
        array  $filters = [],
        array  $columns = ['_source'],
        array  $sort = [],
        string $from = 'now-7d',
        string $to = 'now',
        int    $interval = 300000
    ): string
    {
        self::initializeQueryParameters();
        self::setFilters([]);
        self::setRefreshInterval($interval);
        self::setTimeRange($from, $to);
        self::setColumns($columns);
        self::setAFilters($filters, $index);
        self::setIndex($index);
        self::setQuery();
        self::setSort($sort);
        return self::generateLink();
    }

    public static function generateDocument($index, $id): string
    {
        $url = self::getOsUrl();
        $str = strpos($index, '-');
        $str2 = strpos($index, '.');
        $str = min($str, $str2);
        $discover = substr($index, 0, $str + 1) . '*';
        return "{$url}/app/discover#/doc/{$discover}/{$index}/doc?id={$id}";
    }

    private static function initializeQueryParameters(): void
    {
        self::$queryParams = ['_g' => [], '_a' => []];
    }

    private static function getDate(string|int $date): string
    {
        if (is_numeric($date))
        {
            $time = time() - $date;
            $date = UtilityBase::timeToOSDate($time);
            return "'$date'";
        }

        if (is_string($date))
        {
            $openSearchFormats = [
                '/^now$/i' => 'now',
                '/^now-([0-9]+)d$/i' => 'now-$1d',
                '/^now-([0-9]+)h$/i' => 'now-$1h',
            ];

            foreach ($openSearchFormats as $pattern => $replacement)
            {
                if (preg_match($pattern, $date, $matches))
                {
                    return $date;
                }
            }

            $time = strtotime($date);
            $date = UtilityBase::timeToOSDate($time);

            return "'{$date}'";
        }

        return 'now-7d';
    }

    private static function buildFilters(array $filters, string $index): array
    {
        $result = [];
        $counter = 0;

        foreach ($filters as $key => $value)
        {
            if (is_bool($value))
            {
                $value = UtilityBase::boolToString($value);
            }

            $result[$counter] = [
                'meta' => [
                    'atlas' => '!n',
                    'disabled' => '!f',
                    'index' => "'$index'",
                    'key' => $key,
                    'negate' => '!f',
                    'params' => ['query' => "'$value'"],
                    'type' => 'phrase',
                ],
                'query' => ['match_phrase' => [$key => "'$value'"]],
            ];
            $counter++;
        }

        return array_map(fn($item) => $item, $result);
    }

    private static function generateLink(): string
    {
        $url = self::getOsUrl();
        $encodedA = json_encode(self::$queryParams['_a']);
        $encodedG = json_encode(self::$queryParams['_g']);
        $array = ['"' => '', '{' => '(', '}' => ')', ':' => ':', '[' => '!(', ']' => ')'];
        $encodedA = strtr($encodedA, $array);
        $encodedG = strtr($encodedG, $array);
        return "{$url}/app/discover#/?_g={$encodedG}&_a=$encodedA";
    }

    private static function getOsUrl(): string
    {
        return ConnectionService::getConnectionByConst('OPENSEARCH_LINK')['os_url'] ?? '';
    }
}