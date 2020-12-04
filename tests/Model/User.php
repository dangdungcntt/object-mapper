<?php


namespace Nddcoder\ObjectMapper\Tests\Model;

use DateTime;
use Nddcoder\ObjectMapper\Attributes\ArrayProperty;
use Nddcoder\ObjectMapper\Attributes\JsonProperty;

class User
{
    #[JsonProperty('id')]
    protected string $_id;
    public Subscription $subscription;
    public int $subscribedTimes;
    public bool $active;
    public float $payout;
    public string $userAgent;
    public DeviceInfo $deviceInfo;
    public DateTime $createdAt;
    public DateTime $updatedAt;
    #[JsonProperty('body')]
    public string $description;
    public ?string $title;
    #[ArrayProperty(Message::class)]
    public array $messages;
    public array $messagesViaSetter;
    #[ArrayProperty('array')]
    public array $logs;
    #[ArrayProperty('int')]
    public ?array $groups;

    public function setId($id): void
    {
        $this->_id = $id;
    }

    public function setMessagesViaSetter(#[ArrayProperty(Message::class)] array $message)
    {
        $this->messagesViaSetter = $message;
    }

    public function get_id(): string
    {
        return $this->_id;
    }
}
