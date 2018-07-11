<?php
declare(strict_types=1);

namespace Sokil\TelegramBot\Service\TelegramBotClient\Struct;

use Sokil\TelegramBot\Service\TelegramBotClient\Type\ChatType;

/**
 * This object represents a chat.
 *
 * @link https://core.telegram.org/bots/api#chat
 */
class Chat
{
    /**
     * Unique identifier for this chat
     *
     * @var int
     */
    private $id;

    /**
     * Type of chat, can be either “private”, “group”, “supergroup” or “channel”
     *
     * @var ChatType
     */
    private $type;

    /**
     * @param int $id
     * @param ChatType $type
     */
    public function __construct(int $id, ChatType $type)
    {
        $this->id = $id;
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return ChatType
     */
    public function getType(): ChatType
    {
        return $this->type;
    }

}