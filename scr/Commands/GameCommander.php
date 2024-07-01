<?php

namespace AdminShared\Commands;


class GameCommander
{
    private array $portals;
    private GamePortalInterface $currentGamePort;
    private UserPreference $userPreference;

    /**
     * @param UserPreference $userPreference
     * @param array<GamePortalInterface> $portals
     */
    public function __construct(UserPreference $userPreference, array $portals = [])
    {
        $this->userPreference = $userPreference;
        $this->portals = $portals;
    }

    /**
     * @throws GGException
     */
    private function connectToGamePort(int $gamePortType): void
    {
        if (count($this->portals) === 0)
        {
            throw new GGException('Portals is empty!');
        }

        if ($gamePortType >= count($this->portals))
        {
            $this->userPreference->setGamePortType(0);
            $gamePortType = 0;
        }

        if (!isset($this->portals[$gamePortType]))
        {
            throw new GGException('Portals is not set!');
        }

        $this->currentGamePort = new $this->portals[$gamePortType]();
        $this->userPreference->setGamePortType($gamePortType);
    }

    private function switchGamePort(): bool
    {
        if (!$this->currentGamePort->hasError())
        {
            return false;
        }

        $gamePortType = $this->userPreference->getGamePortType();
        $gamePortType++;
        $this->connectToGamePort($gamePortType);

        return true;
    }

    public function sendGameCommand(string $command, array $message): CommandMessage
    {
        $gamePortType = $this->userPreference->getGamePortType();
        $this->connectToGamePort($gamePortType);

        $answer = $this->currentGamePort->send($command, $message);

        LoggingService::info(LogType::CURL, $answer->getAnswer(),
            ['CommandMessage' => $answer, 'Command' => $command, 'Message' => $message]);

        if ($this->switchGamePort())
        {
            AlarmMessages::sendAlarm(ConfigReader::getConstant('THREAD_EXCEPTION'),
                'Game port switched!');
        }

        return $answer;
    }
}