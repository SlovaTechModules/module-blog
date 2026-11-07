<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Slova\Blog\Model\UrlRewriteGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * bin/magento slova:blog:url-rewrites — rebuild every blog URL (after a store view was
 * added, a prefix was changed with config:set, or rewrites were edited by hand).
 */
class RegenerateUrlRewrites extends Command
{
    /**
     * @param State $appState
     * @param UrlRewriteGenerator $urlRewriteGenerator a proxy (di.xml): bin/magento stays light
     */
    public function __construct(
        private readonly State $appState,
        private readonly UrlRewriteGenerator $urlRewriteGenerator
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('slova:blog:url-rewrites')
            ->setDescription('Rebuild the URL rewrites of the blog home, posts and categories in every store view');
        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->appState->setAreaCode(Area::AREA_ADMINHTML);
        } catch (LocalizedException) {
            // Area already set.
        }

        $result = $this->urlRewriteGenerator->regenerateAll();
        $output->writeln(sprintf(
            '<info>Blog URLs rebuilt: home, %d post(s), %d categor%s.</info>',
            $result['posts'],
            $result['categories'],
            $result['categories'] === 1 ? 'y' : 'ies'
        ));
        return Command::SUCCESS;
    }
}
