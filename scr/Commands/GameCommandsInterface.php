<?php

namespace AdminShared\Commands;


interface GameCommandsInterface
{
    public function sendMessage(int $receiverId, string $message,
                                SupportChatParametersInterface $supportChatParameters): CommandMessage;

    public function editMessage(int $receiverId, int $messageId, string $newText,
                                SupportChatParametersInterface $supportChatParameters): CommandMessage;

    public function deleteMessage(int $receiverId, int $messageId): CommandMessage;
}