<?php

namespace AdminShared\Commands;

interface GamePortalInterface
{
    public function send($command, $message): CommandMessage;

    public function hasError(): bool;
}