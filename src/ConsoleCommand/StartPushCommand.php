<?php
declare(strict_types=1);

/**
 * This file is part of the PHP Telegram Starter Kit.
 *
 * (c) Dmytro Sokil <dmytro.sokil@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sokil\TelegramBot\ConsoleCommand;

use React\EventLoop\LoopInterface;
use Sokil\TelegramBot\Service\HttpServer\HttpServer;
use Sokil\TelegramBot\Service\TelegramBotClient\TelegramBotClientInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Get updates from Telegram bot from webhooks
 *
 * @see https://core.telegram.org/bots/webhooks
 * @see https://core.telegram.org/bots/api#setwebhook
 */
class StartPushCommand extends Command
{
    public static $defaultName = 'start:push';

    /**
     * HTTP port of application to handle Telegram Update requests
     */
    public const DEFAULT_HTTP_PORT = 8080;

    /**
     * @var LoopInterface
     */
    private $eventLoop;

    /**
     * @var TelegramBotClientInterface
     */
    private $telegram;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var HttpServer
     */
    private $httpServer;

    /**
     * @param LoopInterface $eventLoop
     * @param TelegramBotClientInterface $telegram
     * @param RouterInterface $router
     * @param HttpServer $httpServer
     */
    public function __construct(
        LoopInterface $eventLoop,
        TelegramBotClientInterface $telegram,
        RouterInterface $router,
        HttpServer $httpServer
    ) {
        parent::__construct(null);

        $this->eventLoop = $eventLoop;
        $this->telegram = $telegram;
        $this->router = $router;
        $this->httpServer = $httpServer;
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $webHookPort = getenv('WEBHOOK_PORT');
        if ($webHookPort === false) {
            $webHookPort = self::DEFAULT_HTTP_PORT;
        }

        $this
            ->addOption(
                'httpPort',
                'p',
                InputOption::VALUE_OPTIONAL,
                'HTTP port to listen callbacks from Telegram',
                $webHookPort
            )
            ->addOption(
                'skipCheckWebHookUrl',
                '',
                InputOption::VALUE_NONE,
                'Do not check correct webhook and try to set it otherwise'
            )
            ->setDescription('Run bot server and get updates from webhooks');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return null|int null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('skipCheckWebHookUrl')) {
            // build absolute URL to webhook
            $telegramWebHookUrl = $this->router->generate(
                'telegramWebHook',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            try {
                // check if webhook configured
                $webHookInfo = $this->telegram->getWebHookInfo();
                if ($webHookInfo->getUrl() === null) {
                    // set web hook
                    $output->writeln(
                        sprintf('<info>Setting web hook to %s</info>', $webHookInfo->getUrl()),
                        OutputInterface::VERBOSITY_VERBOSE
                    );

                    $this->telegram->setWebhook($telegramWebHookUrl);
                } else if ($webHookInfo->getUrl() !== $telegramWebHookUrl) {
                    $output->writeln(
                        sprintf(
                            '<info>Web hook already set to %s, replace with %s</info>',
                            $webHookInfo->getUrl(),
                            $telegramWebHookUrl
                        ),
                        OutputInterface::VERBOSITY_VERBOSE
                    );

                    $this->telegram->setWebhook($telegramWebHookUrl);
                }
            } catch (\Throwable $e) {
                $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
                return 1;
            }
        }

        $httpPort = (int)$input->getOption('httpPort');
        if (empty($httpPort)) {
            throw new \InvalidArgumentException('HTTP port not specified');
        }

        // start HTTP server
        $this->httpServer->create($httpPort);

        $output->writeln(
            sprintf('<info>HTTP server started to listen port %d</info>', $httpPort),
            OutputInterface::VERBOSITY_VERBOSE
        );

        // start event loop
        $this->eventLoop->run();

        return 0;
    }
}
