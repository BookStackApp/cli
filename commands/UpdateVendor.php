<?php
namespace BookStackCli;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateVendor extends BaseCommand
{
    protected $zipUrl = 'https://f001.backblazeb2.com/file/bookstackapp/vendor/%s.zip';
    protected $checksumUrl = 'https://www.bookstackapp.com/checksums/vendor/%s';

    protected function configure()
    {
        $this->setName('update:vendor')
            ->setDescription('Updates the vendor directory without composer')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the update and ignore validating the update file.')
            ->setHelp('This command updates the application dependencies stored in the /vendor/ folder.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->checkBookStackDir($input, $output)) return;
        $isVerbose = $output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE;

        // Get BookStack version and file locations
        $version = $this->getVersion();
        $zipLocation = sprintf($this->zipUrl, $version);
        $checksumLocation = sprintf($this->checksumUrl, $version);

        // Download vendor zip file
        if ($isVerbose) $output->writeln("<info>Downloading zip of vendor folder.</info>");
        $tempZipLocation = $this->getTempZipLocation();
        try {
            file_put_contents($tempZipLocation, fopen($zipLocation, 'r'));
        } catch (\Exception $e) {
            $output->writeln("<error>Download of vendor zip failed with error:\n".$e->getMessage()."</error>");
        }

        // Check file hash
        if ($isVerbose) $output->writeln("<info>Validating zip contents.</info>");
        $checksum = substr(trim(file_get_contents($checksumLocation)), 0, 50);
        $fileHash = sha1_file($tempZipLocation);
        if ($checksum !== $fileHash) {
            $output->writeln("<error>Update file did not match checksum</error>");
            $output->writeln("<error>Vendor file checksum (sha1): " . $fileHash . "</error>");
            $output->writeln("<error>Expected checksum (sha1): " . $checksum . "</error>");
            if (!$input->hasOption('force')) {
                $this->cleanUp();
                return;
            }
        }

        $zip = new \ZipArchive();
        $zip->open($tempZipLocation);

        $vendorLocation = getcwd() . '/vendor';
        if (file_exists($vendorLocation)) {
            if ($isVerbose)  $output->writeln("<info>Deleting previous vendor folder</info>");
            $this->rrmdir($vendorLocation);
        }
        if ($isVerbose) $output->writeln("<info>Extracting vendor contents</info>");
        $zip->extractTo(getcwd());
        $zip->close();
        if ($isVerbose) $output->writeln("<info>Deleting temporary zip file</info>");
        chmod($tempZipLocation, 0755);
        unlink($tempZipLocation);
        $output->writeln("<info>Vendor folder successfully updated</info>");
    }

    protected function getTempZipLocation()
    {
        return getcwd() . '/vendor.tmp.zip';
    }

    protected function cleanUp()
    {
        if (file_exists($this->getTempZipLocation())) {
            unlink($this->getTempZipLocation());
        }
    }

    /**
     * Recrusively delete a directory
     * @param $dir
     */
    protected function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir."/".$object))
                        $this->rrmdir($dir."/".$object);
                    else
                        unlink($dir."/".$object);
                }
            }
            rmdir($dir);
        }
    }
}
