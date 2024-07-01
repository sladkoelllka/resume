<?php

namespace AdminShared\Commands;


abstract class AbstractGameCommands implements GameCommandsInterface
{
    protected const PERMISSION_ERROR = 'You don\'t have permission to use this command!';

    private GameCommander $gameCommander;

    public function __construct()
    {
        $this->gameCommander = new GameCommander();
    }

    public function getGameCommander(): GameCommander
    {
        return $this->gameCommander;
    }

    /**
     * @throws GGException
     */
    protected function sendCommandAndAlarm(string $command, array $data): CommandMessage
    {
        $result = $this->gameCommander->sendGameCommand($command, $data);

        AlarmMessages::sendAlarm(ConfigReader::getConstant('THREAD_RISK_COMMANDS'),
            "{$command}: {$result->getAnswer()}", $data);

        return $result;
    }
}