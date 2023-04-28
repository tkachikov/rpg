<?php

namespace App;

use App\Models\User;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;

class Action
{
    private readonly User $user;

    private string $key;

    /**
     * @param User $user
     * @return $this
     */
    public function user(User $user): self
    {
        $this->user = $user;
        $this->key = 'actions-'.$this->user->id;

        return $this;
    }

    /**
     * @param string $method
     * @param array $params
     * @return void
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     */
    public function save(string $method, array $params): void
    {
        $actions = $this->get();
        $actions[$method] = $params;
        cache()->set($this->key, $actions);
    }

    /**
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function get(): array
    {
        return cache()->get($this->key) ?? [];
    }

    /**
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    public function getAndFlush(): array
    {
        $actions = $this->get();
        cache()->delete($this->key);

        return $actions;
    }
}
