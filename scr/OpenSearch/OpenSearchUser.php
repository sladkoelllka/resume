<?php

namespace AdminShared\OpenSearch;

class OpenSearchUser
{
    private string $host;
    private string $port;
    private ?string $user;
    private ?string $password;
    private ?string $env;

    public function __construct(string $host, string $port, ?string $user = null, ?string $password = null,
                                ?string $env = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
        $this->env = $env;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): string
    {
        return $this->port;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getEnv(): ?string
    {
        return $this->env;
    }
}