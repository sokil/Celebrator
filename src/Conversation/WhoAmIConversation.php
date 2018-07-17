<?php
declare(strict_types=1);

namespace Sokikl\TelegramBot\Conversation;

use Sokil\TelegramBot\Service\ConversationManager\AbstractConversation;
use Sokil\TelegramBot\Service\TelegramBotClient\Struct\Update;

class WhoAmIConversation extends AbstractConversation
{
    /**
     * @param Update $update
     */
    public function apply(Update $update): void
    {
        $chatId = $update->getMessage()->getChat()->getId();

        $userId = $update->getMessage()->getFrom()->getId();
        $firstName = $update->getMessage()->getFrom()->getFirstName();
        $lastName = $update->getMessage()->getFrom()->getLastName();
        $userName = $update->getMessage()->getFrom()->getUserName();

        $message = sprintf(
            "User Id: %s\nLast name :%s\nFirst name: %s\nUsername: %s",
            $userId,
            $lastName,
            $firstName,
            $userName
        );

        $this->telegramBotClient->sendMessage(
            $chatId,
            $message
        );
    }
}