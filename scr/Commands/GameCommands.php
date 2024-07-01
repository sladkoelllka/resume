<?php

namespace AdminShared\Commands;

class GameCommands extends AbstractGameCommands
{
    /**
     * Команда для создания личных писем от администрации игроку
     *
     * @param int $accountId
     * @param CategoryEnum $category
     * @param string $caption
     * @param string $description
     * @param array $buttonsInfo
     * @param array $products
     * @return CommandMessage
     * @throws GGException
     */
    public function mail(int $accountId, CategoryEnum $category, string $caption, string $description,
                         array $buttonsInfo = [], array $products = []): CommandMessage
    {
        if (!Authentication::hasPermission('use.command.mail'))
        {
            return new CommandMessage(self::PERMISSION_ERROR);
        }

        $command = 'MAIL';
        $data['type'] = 'addLetter';
        $data['accountID'] = $accountId;

        $data['message'] = [
            'caption' => $caption,
            'category' => $category->name,
            'description' => $description,
            'spriteName' => 'SpriteTest',
            'buttonsInfo' => $this->setButtonsInfoToCommand($buttonsInfo),
            'products' => $this->setProductsToCommand($products),
        ];

        return $this->sendCommandAndAlarm($command, $data);
    }

    /**
     * Команда для выдачи награды игроку.
     *
     * @param int $accountId Id аккаунта, на который необходимо выдать награду
     * @param array $products Список наград (заполняются по принципу: type, typeId, amount)
     * @return CommandMessage
     * @throws GGException
     */
    public function billing(int $accountId, array $products): CommandMessage
    {
        if (!Authentication::hasPermission('use.command.billing'))
        {
            return new CommandMessage(self::PERMISSION_ERROR);
        }

        $command = 'BILLING';
        $data = [
            'type' => 'newInvoice',
            'accountID' => $accountId,
            'provider' => APP_NAME,
            'product' => $this->setProductsToCommand($products),
        ];

        return $this->sendCommandAndAlarm($command, $data);
    }

    /**
     * Уменьшение количества валюты у игрока.
     *
     * @param int $accountId Id аккаунта, на котором необходимо изменить количество валюты
     * @param int $currencyId Id валюты, количество которой необходимо уменьшить
     * @param int $amount Количество валюты, которое необходимо забрать у игрока
     * @return CommandMessage
     * @throws GGException
     */
    public function decreaseCurrency(int $accountId, int $currencyId, int $amount): CommandMessage
    {
        if (!Authentication::hasPermission('use.command.billing'))
        {
            return new CommandMessage(self::PERMISSION_ERROR);
        }

        $command = 'BILLING';
        $data = [
            'type' => 'takeAway',
            'accountID' => $accountId,
            'provider' => APP_NAME,
            'currencyID' => $currencyId,
            'amount' => $amount,
        ];

        return $this->sendCommandAndAlarm($command, $data);
    }

    /**
     * Загрузка сохраненного боя.
     *
     * @param string $battleId Id боя
     * @return CommandMessage
     */
    public function getReplay(string $battleId): CommandMessage
    {
        $command = 'BATTLE';
        $data['type'] = 'loadReplay';
        $data['battleId'] = $battleId;

        return $this->getGameCommander()->sendGameCommand($command, $data);
    }

    /**
     * Позволяет отправить сообщение игроку от лица саппорта.
     *
     * @param int $receiverId Идентификатор игрока, которому отправляется сообщение
     * @param string $message Текст сообщения
     * @param SupportChatParametersInterface $supportChatParameters
     * @return CommandMessage
     */
    public function sendMessage(int $receiverId, string $message,
                                SupportChatParametersInterface $supportChatParameters): CommandMessage
    {
        if (!Authentication::hasPermission('use.command.send.message'))
        {
            return new CommandMessage(self::PERMISSION_ERROR);
        }

        $command = 'ACTION';
        $data['type'] = 'sendMessageFromSupport';
        $data['receiverId'] = $receiverId;
        $data['message'] = $message;

        $data += $supportChatParameters->toArray();

        return $this->getGameCommander()->sendGameCommand($command, $data);
    }

    /**
     * @param int $receiverId Идентификатор игрока, которому редактируем сообщение
     * @param int $messageId Идентификатор сообщения, которое редактируем
     * @param string $newText Новый текст сообщения
     * @param SupportChatParametersInterface $supportChatParameters
     * @return CommandMessage
     */
    public function editMessage(int $receiverId, int $messageId, string $newText,
        SupportChatParametersInterface $supportChatParameters): CommandMessage
    {
        if (!Authentication::hasPermission('use.command.send.message'))
        {
            return new CommandMessage(self::PERMISSION_ERROR);
        }

        $command = 'ACTION';
        $data['type'] = 'editMessageFromSupport';
        $data['messageId'] = $messageId;
        $data['receiverId'] = $receiverId;
        $data['newText'] = $newText;

        $data += $supportChatParameters->toArray();

        return $this->getGameCommander()->sendGameCommand($command, $data);
    }

    /**
     * @param int $receiverId Идентификатор игрока, которому удаляем сообщение
     * @param int $messageId Идентификатор сообщения, которое удаляем
     * @return CommandMessage
     */
    public function deleteMessage(int $receiverId, int $messageId): CommandMessage
    {
        if (!Authentication::hasPermission('use.command.send.message'))
        {
            return new CommandMessage(self::PERMISSION_ERROR);
        }

        $command = 'ACTION';
        $data['type'] = 'removeMessageFromSupport';
        $data['receiverId'] = $receiverId;
        $data['messageId'] = $messageId;

        return $this->getGameCommander()->sendGameCommand($command, $data);
    }

    private function convertBanReasons($reasons): array
    {
        $reasons = array_unique($reasons);
        $result = [];

        foreach ($reasons as $reason)
        {
            $reason = (int)$reason;

            if ($reason === -1)
            {
                continue;
            }

            $result[] = $reason;
        }

        return $result;
    }

    private function setProductsToCommand(array $products): array
    {
        $result = [];

        foreach ($products as $product)
        {
            if (!isset($product['type']) || !isset($product['typeId']) || !isset($product['amount']))
            {
                continue;
            }

            $result[] = [
                'type' => $product['type'],
                'typeId' => $product['typeId'],
                'amount' => $product['amount']
            ];
        }

        return $result;
    }

    private function setButtonsInfoToCommand(array $buttonsInfo): array
    {
        $result = [];

        foreach ($buttonsInfo as $buttonInfo)
        {
            if (!isset($buttonInfo['url']) || !isset($buttonInfo['labelName']) || !isset($buttonInfo['spriteName']))
            {
                continue;
            }

            $result[] = [
                'url' => $buttonInfo['url']->value,
                'labelName' => $buttonInfo['labelName']->value,
                'spriteName' => $buttonInfo['spriteName']->value,
            ];
        }

        return $result;
    }
}