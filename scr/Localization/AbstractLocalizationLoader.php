<?php

namespace AdminShared\Localization;

abstract class AbstractLocalizationLoader implements LocalizationLoaderInterface
{
    protected const PATH_MAPPING = '';

    protected array $sourceStrings = [];
    protected array $localizationStrings = [];
    protected array $rawLocalizationStrings = [];
    protected array $mappingStrings = [];
    protected ?string $locale = null;

    protected EntityTypeInterface $currentType;

    public function __construct(EntityTypeInterface $entityType)
    {
        $this->currentType = $entityType;
    }

    public function getNameByMapping($type = null): string
    {
        if ($type == null)
        {
            $type = $this->currentType;
        }

        if ($this->mappingStrings == null)
        {
            $this->mappingStrings = UtilityBase::readJsonFile(self::PATH_MAPPING);
        }

        $result = $this->mappingStrings[$type->value]['name'] ?? false;

        if ($result === false)
        {
            return 'Not found';
        }

        return $result;
    }

    public function getAllData($id, $type = null): array
    {
        if ($type == null)
        {
            $type = $this->currentType;
        }

        if ($id == -1 || $id === null || $id === '')
        {
            return [];
        }

        $this->getStringSource($type);
        $arrayData = $this->sourceStrings[$type->value][$id] ?? false;

        if (!$arrayData)
        {
            $className = $this->getNameByMapping($this->currentType);
            LoggingService::error(LogType::PARSE,
                "Id not found in SourceStrings. Id: {$id}; Type: {$className}"
            );

            return [];
        }

        return $arrayData;
    }

    public function getAllSourceIds($type = null): array
    {
        if ($type == null)
        {
            $type = $this->currentType;
        }

        $this->getStringSource($type);

        return array_keys($this->sourceStrings[$type->value]);
    }

    public function getLocalizationString(string|int|null $id, ?string $locale = null): string|array
    {
        if ($locale == null)
        {
            $locale = $this->locale;
        }

        return $this->localizationStrings[$id][$locale] ?? [];
    }

    public function getRawLocalizationString(string|int|null $id, ?string $locale = null): string|array
    {
        if ($locale == null)
        {
            $locale = $this->locale;
        }

        return $this->rawLocalizationStrings[$id][$locale] ?? [];
    }

    public function setLocalizationString(string|int|null $id, ?string $locale = null, string $text = ''): void
    {
        if ($locale == null)
        {
            $locale = $this->locale;
        }

        $this->localizationStrings[$id][$locale] = $text;
    }

    public function getAllRawLocalizationString(): array
    {
        return $this->rawLocalizationStrings ?? [];
    }
}