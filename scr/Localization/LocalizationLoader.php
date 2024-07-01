<?php

namespace AdminShared\Localization;

class LocalizationLoader extends AbstractLocalizationLoader
{
    protected const PATH_MAPPING = '/data/strings/mapping.jsn';
    protected const PATH_STRINGS_CLIENT = '/data/strings/str_client.jsn';
    protected const FOLDER = '/data/strings/{FILE}.jsn';

    public function getStringSource($type = null, $localeId = null): void
    {
        $this->currentType = EntityType::Client;

        if ($type != null)
        {
            $this->currentType = $type;
        }

        if ($localeId == null)
        {
            $this->locale = SystemLanguage::getShortName();
        }

        if ($localeId != null)
        {
            $this->locale = SystemLanguage::getShortName($localeId);
        }

        if ($this->mappingStrings == null)
        {
            $this->mappingStrings = UtilityBase::readJsonFile(self::PATH_MAPPING)
                ?? throw new GGException('Mapping file not found');
        }

        if ($this->rawLocalizationStrings == null)
        {
            $this->localizationStrings = [];
            $this->rawLocalizationStrings = UtilityBase::readJsonFile(self::PATH_STRINGS_CLIENT)['str']
                ?? throw new GGException('Strings file not found');
        }

        $currentType = $this->currentType->value;

        if (!isset($this->sourceStrings[$currentType]) && $this->currentType !== EntityType::Client)
        {
            $fileName = $this->mappingStrings['Mapping'][$currentType]['file'];
            $filename = str_replace('{FILE}', $fileName, self::FOLDER);
            $this->sourceStrings[$currentType] = UtilityBase::readJsonFile($filename)[$currentType];
        }
    }
}