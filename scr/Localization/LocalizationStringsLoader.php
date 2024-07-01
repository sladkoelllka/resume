<?php


namespace AdminShared\Localization;

class LocalizationStringsLoader
{
    private const PATTERN_ARGUMENTS = '/<[^}#=\/]*?({?#([0-9 \w]*)(?:<(.*?)>)*}?)[^#]*?>/';
    private const PATTERN_MAIN = '/({ *#(.*?)(?:<(?:(<(?:.*)>|.*?))> *)*})/';
    private const MAX_COUNT_ITERATION_PARSE = 20;

    private static array $massCheckSymbols = ['{', '}'];
    private const ARGUMENT_ERROR = 'ARGUMENT_ERROR';

    private int $indexIteration;
    private LocalizationLoaderInterface $localizationLoader;

    public function __construct(LocalizationLoaderInterface $localizationLoader)
    {
        $this->localizationLoader = $localizationLoader;
    }

    public function getRawLocalization(): array
    {
        $this->localizationLoader->getStringSource();
        return $this->localizationLoader->getAllRawLocalizationString();
    }

    private function isLoopError(&$text): bool
    {
        if (++$this->indexIteration > self::MAX_COUNT_ITERATION_PARSE)
        {
            $className = $this->localizationLoader->getNameByMapping();
            LoggingService::error(LogType::PARSE, "Looping error: {$text}; Type: {$className}");

            $text = 'Error Parse';
            return true;
        }

        return false;
    }

    private function parseLocalizationStringIteration($text)
    {
        if ($text == '' || $text == null)
        {
            return null;
        }

        if ($this->isLoopError($text))
        {
            return $text;
        }

        self::checkSymbols($text);
        $text = $this->regexParsPattern($text, self::PATTERN_ARGUMENTS);
        return $this->regexParsPattern($text, self::PATTERN_MAIN);
    }

    public function tryGetParseLocalizationString($id, &$text): bool
    {
        $result = $this->localizationLoader->getLocalizationString($id);

        if ($result != null)
        {
            $text = $result;
            return true;
        }

        $textExists = $this->localizationLoader->getRawLocalizationString($id);

        if ($textExists != null)
        {
            $text = $textExists;
            $text = self::parseLocalizationStringIteration($text);
            $this->localizationLoader->setLocalizationString($id, text: $text);
            $this->indexIteration = 0;
            return true;
        }

        return false;
    }

    private function regexParsPattern($text, $pattern)
    {
        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);
        preg_match_all($pattern, $text, $matchesPositions, PREG_OFFSET_CAPTURE);
        $result = [];
        $index = 0;

        foreach ($matches as $key => $element)
        {
            $matchStartIndex = intval($matchesPositions[1][$key][1]);
            $matchEndIndex = strlen($element[1]) - 1;
            $result[] = substr($text, $index, $matchStartIndex - $index);
            $result[] = self::parsArguments($element);
            $index = $matchStartIndex + $matchEndIndex + 1;
        }

        $result[] = substr($text, $index, strlen($text) - $index);
        $resultText = implode('', $result);

        if (count($matches) > 0)
        {
            if ($this->isLoopError($text))
            {
                return $text;
            }

            return $this->regexParsPattern($resultText, $pattern);
        }

        return $resultText;
    }

    private function parsArguments($match)
    {
        $localizedString = '';
        $idArgument = str_replace(' ', '', $match[2]);

        $this->tryGetParseLocalizationString($idArgument, $localizedString);

        $text = $match[0];
        $regMtch3 = '/(<(<(.*)>|.*?)>+)/';
        preg_match_all($regMtch3, $text, $matches3, PREG_SET_ORDER);

        if ((count($match) > 2 && count($matches3) > 0))
        {
            $arguments = [];

            foreach ($matches3 as $match3)
            {
                $arguments[] = $match3[2];
            }

            $localizedString = $this->parseLocalizationStringFormat($localizedString, $arguments);
        }

        return $localizedString;
    }

    private function parseLocalizationStringFormat($value, $arguments)
    {
        try
        {
            preg_match_all('/\{(\d+)\}/', $value, $matches);
            sort($matches[1]);

            foreach ($matches[1] as $key => $index)
            {
                $value = str_replace('{' . $index . '}', $arguments[$key], $value);
            }
        }
        catch (GGException $e)
        {
            LoggingService::error(LogType::EXCEPTIONS, $e);
        }

        return $value;
    }

    private function checkSymbols($text): void
    {
        $symbolsError = '';

        foreach (self::$massCheckSymbols as $checkSymbols)
        {
            $symbolsError .= self::checkEqualsCountSymbols($text, $checkSymbols[0], $checkSymbols[1]);
        }
    }

    private function checkEqualsCountSymbols($text, $symbolFirst, $symbolSecond): array|string
    {
        $symbolsStack = [];
        $result = [];

        foreach ($text as $symbolChar)
        {
            if ($symbolChar == $symbolFirst)
            {
                $symbolsStack[] = $symbolChar;
                continue;
            }

            if ($symbolChar != $symbolSecond)
            {
                continue;
            }

            if (count($symbolsStack) == 0)
            {
                $symbolsStack[] = $symbolSecond;
                break;
            }

            array_pop($symbolsStack);
        }

        if (count($symbolsStack) > 0)
        {
            $result = implode('', $result);
        }

        return $result;
    }

    public function parseLocalizationString(string|int|null $id, $localeId = null): array|string|null
    {
        if ($id == -1 || $id === null || $id === '')
        {
            return $id;
        }

        if (UtilityBase::isInteger($id))
        {
            $id = sprintf('{#%d}', $id);
        }

        $this->indexIteration = 0;
        $this->localizationLoader->getStringSource(localeId: $localeId);
        $text = self::parseLocalizationStringIteration($id);

        if ($text == null)
        {
            return null;
        }

        return UguiToHtmlHelper::replaceColorTag($text);
    }

    public function getAllData(int $id, $type): array
    {
        $this->localizationLoader->getStringSource($type);
        return $this->localizationLoader->getAllData($id, $type);
    }

    public function getAllSourceIds($type): array
    {
        $this->localizationLoader->getStringSource($type);
        return $this->localizationLoader->getAllSourceIds($type);
    }
}