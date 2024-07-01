<?php

namespace AdminShared\Commands;

class CommandMessage
{
    private string $answer;
    private string $convertedAnswer;
    private bool $hasError;

    public function __construct(string $answer, bool $hasError = true)
    {
        $this->answer = $answer;
        $this->hasError = $hasError;
        $this->convertedAnswer = $answer;
    }

    public function getAnswer(): string
    {
        return $this->answer;
    }

    public function setAnswer(string $answer): void
    {
        $this->answer = $answer;
    }

    public function getConvertedAnswer(): string
    {
        return $this->convertedAnswer;
    }

    public function setConvertedAnswer(string $convertedAnswer): void
    {
        $this->convertedAnswer = $convertedAnswer;
    }

    public function hasError(): bool
    {
        return $this->hasError;
    }

    public function setHasError(bool $hasError): void
    {
        $this->hasError = $hasError;
    }
}