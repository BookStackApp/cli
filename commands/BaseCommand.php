<?php
namespace BookStackCli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BaseCommand extends Command
{

    /**
     * Perform a simple check to see if a command is currently being
     * run from the BookStack directory.
     * @return bool
     */
    private function isBookStackDir()
    {
        $dir = getcwd();
        $composer = $dir . '/composer.json';
        $versionFile = $dir . '/version';

        if (!file_exists($composer) || !file_exists($versionFile)) return false;

        $composerData = json_decode(file_get_contents($composer));
        if (!isset($composerData->name)) return false;
        if (strpos(strtolower(explode('/', $composerData->name)[1]), 'bookstack') === false) return false;

        return true;
    }

    /**
     * Check if currently in the BookStack directory and output to command line if not.
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    protected function checkBookStackDir(InputInterface $input, OutputInterface $output)
    {
        if ($this->isBookStackDir()) return true;
        $output->writeln("<error>This command must be ran from the BookStack installation folder</error>");
        return false;
    }

    /**
     * Get the current version of BookStack.
     * @return string
     */
    protected function getVersion()
    {
        $versionFile = getcwd() . '/version';
        return trim(file_get_contents($versionFile));
    }

}
