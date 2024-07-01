<?php


namespace AdminShared\Localization;

interface LocalizationLoaderInterface
{
    public function getStringSource($type = null, $localeId = null): void;

    public function getNameByMapping($type = null): string;

    public function getAllData($id, $type = null): array;

    public function getAllSourceIds($type = null): array;

    public function getLocalizationString(string|int|null $id, ?string $locale = null): string|array;

    public function setLocalizationString(string|int|null $id, ?string $locale = null, string $text = ''): void;

    public function getRawLocalizationString(string|int|null $id, ?string $locale = null): string|array;

    public function getAllRawLocalizationString(): array;
}