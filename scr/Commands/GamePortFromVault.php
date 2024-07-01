<?php

namespace AdminShared\Commands;

class GamePortFromVault implements GamePortalInterface
{
    private bool $isError;
    private ?string $ip;
    private ?string $port;

    public function __construct(UserPreference $userPreference)
    {
        $this->isError = false;

        $cluster = $userPreference->getClusterName();

        $gsUrl = HttpRequestHandler::isLocalhost() ? 'gs_lan' : 'gs_url';
        $url = ConnectionService::getConnection("/public/cluster/{$cluster}")[$gsUrl];

        list($this->ip, $this->port) = $this->getHost($url);

        if ($this->ip === null || $this->port === null)
        {
            $this->isError = true;
            AlarmMessages::sendAlarm(THREAD_PORT, 'Vault IP or port is null!');
        }
    }

    private function getHost($url): array
    {
        $curlHandle = curl_init($url);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, 'POST');

        $content = curl_exec($curlHandle);
        curl_close($curlHandle);
        $messageVault = json_decode($content, true)['message'];
        $messageJSON = json_decode($messageVault, true);

        return [$messageJSON['ip'], $messageJSON['port']];
    }

    #[Performance]
    public function send($command, $message, $getResultAnswer = false): CommandMessage
    {
        if ($this->hasError())
        {
            return new CommandMessage('Fatal send from Vault!');
        }

        $url = "{$this->ip}:{$this->port}";
        $urlRequest = "{$url}/{$command}";

        list($answer, $curlInfo, $requestAnswer) = CurlHelper::send($urlRequest, $message);
        $httpCode = $curlInfo['http_code'];

        $hasError = !CommandMessageHelper::parseHttpCode($httpCode);
        $answerMessage = CommandMessageHelper::getAnswer($answer, $httpCode, $url);
        $this->isError = $hasError;

        $commandMessage = new CommandMessage($answer, $hasError);
        $commandMessage->setConvertedAnswer($answerMessage);

        return $commandMessage;
    }

    public function hasError(): bool
    {
        return $this->isError;
    }
}