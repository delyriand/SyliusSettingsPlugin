<?php

/*
 * This file is part of Monsieur Biz' Settings plugin for Sylius.
 *
 * (c) Monsieur Biz <sylius@monsieurbiz.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MonsieurBiz\SyliusSettingsPlugin\Command;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use MonsieurBiz\SyliusSettingsPlugin\Exception\SettingsException;
use MonsieurBiz\SyliusSettingsPlugin\Formatter\SettingsFormatterInterface;
use MonsieurBiz\SyliusSettingsPlugin\Provider\SettingProviderInterface;
use MonsieurBiz\SyliusSettingsPlugin\Settings\RegistryInterface;
use MonsieurBiz\SyliusSettingsPlugin\Settings\SettingsInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'monsieurbiz:settings:set',
    description: 'Set a settings value for a given path',
    aliases: ['mbiz:settings:set'],
    hidden: false
)]
class SetSettingsCommand extends Command
{
    private const ARGUMENT_ALIAS = 'alias';

    private const ARGUMENT_PATH = 'path';

    private const OPTION_CHANNEL = 'channel';

    private const OPTION_LOCALE = 'locale';

    private const OPTION_TYPE = 'type';

    private const ARGUMENT_VALUE = 'value';

    public function __construct(
        private RegistryInterface $settingsRegistry,
        private ChannelRepositoryInterface $channelRepository,
        private SettingProviderInterface $settingProvider,
        private EntityManagerInterface $settingManager,
        private SettingsFormatterInterface $settingsFormatter,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command allows you to set a settings value for a given path')
            ->addArgument(self::ARGUMENT_ALIAS, InputArgument::REQUIRED, 'Alias of the settings like {vendor}.{plugin} from the setting definition')
            ->addArgument(self::ARGUMENT_PATH, InputArgument::REQUIRED, 'Path of the settings')
            ->addArgument(self::ARGUMENT_VALUE, InputArgument::REQUIRED, 'Value of the settings')
            ->addOption(self::OPTION_TYPE, 't', InputOption::VALUE_OPTIONAL, 'Type of the settings', null)
            ->addOption(self::OPTION_CHANNEL, 'c', InputOption::VALUE_OPTIONAL, 'Channel code')
            ->addOption(self::OPTION_LOCALE, 'l', InputOption::VALUE_OPTIONAL, 'Locale code')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            /** @var string $alias */
            $alias = $input->getArgument(self::ARGUMENT_ALIAS);
            /** @var string $path */
            $path = $input->getArgument(self::ARGUMENT_PATH);
            $channelCode = $input->getOption(self::OPTION_CHANNEL);
            /** @var ?string $locale */
            $locale = $input->getOption(self::OPTION_LOCALE);

            $channel = null;
            if (null !== $channelCode) {
                /** @var ?ChannelInterface $channel */
                $channel = $this->channelRepository->findOneBy(['code' => $channelCode]);
            }

            /** @var ?SettingsInterface $settings */
            $settings = $this->settingsRegistry->getByAlias($alias);

            if (null === $settings) {
                throw new SettingsException(\sprintf('The alias "%s" is not valid.', $alias));
            }

            ['vendor' => $vendor, 'plugin' => $plugin] = $settings->getAliasAsArray();
            $setting = $this->settingProvider->getSettingOrCreateNew($vendor, $plugin, $path, $locale, $channel);

            /** @var string $type */
            $type = $input->getOption(self::OPTION_TYPE) ?? $setting->getStorageType();
            $this->settingProvider->validateType($type);

            $value = $input->getArgument(self::ARGUMENT_VALUE);

            $this->settingProvider->resetExistingValue($setting);
            $setting->setStorageType($type);
            /** @phpstan-ignore-next-line */
            $setting->setValue($this->settingsFormatter->formatValue($type, $value));

            $this->settingManager->persist($setting);
            $this->settingManager->flush();
        } catch (Exception $e) {
            $output->writeln(\sprintf('<error>%s</error>', $e->getMessage()));

            return Command::FAILURE;
        }

        $output->writeln(\sprintf('<info>%s</info>', 'The setting has been saved'));

        return Command::SUCCESS;
    }
}
