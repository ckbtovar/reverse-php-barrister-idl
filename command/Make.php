<?
namespace CreditKarma\Barrister\Tools\Idl2Php\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Make extends Command {

    protected function configure() {
        $this
            ->setName('make')
            ->setDescription('Converts IDL JSON file into PHP stub classes')
            ->setDefinition(array(
                new InputArgument('json', InputArgument::REQUIRED, 'IDL JSON file'),
                new InputArgument('output', InputArgument::REQUIRED, 'Output directory'),
                new InputArgument('package', InputArgument::OPTIONAL, 'Package name')
            ));
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $package = $input->getArgument('package');
        $json = $input->getArgument('json');
        $outputDir = $input->getArgument('output');

        if (strpos($package, '.')) {
            $package = str_replace('.', "\\", $package);
        }

        $jsonData = json_decode(file_get_contents($json));
        if (!$jsonData) {
            throw new \Exception('Could not open and decode input JSON file!');
        }

        $version = false;
        $composerJson = json_decode(file_get_contents('composer.json'));
        if (isset($composerJson->version)) {
            $version = $composerJson->version;
        }
        if (!$version) {
            throw new \Exception('Version missing from composer.json!');
        }

        $parser = new \IdlParser($jsonData, $package, $outputDir, $version);
        $output->writeln(sprintf('Generating code for <info>%s</info> to <info>%s</info>', $json, $outputDir));
        $parser->parse();
    }
}