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
                new InputArgument('package', InputArgument::OPTIONAL, 'Package name'),
                new InputArgument('enum_base', InputArgument::OPTIONAL, 'Optional base class from which Enums extend')
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

        $enumBase = '\\' . $input->getArgument('enum_base');

        $version = false;
        
        $parser = new \IdlParser($jsonData, $package, $outputDir, $enumBase, $version);
        $output->writeln(sprintf('Generating code for <info>%s</info> to <info>%s</info>', $json, $outputDir));
        $parser->parse();
        $output->writeln(sprintf('<info>Finished!</info> Generated %s classes.', $parser->getSaveCount()));
    }
}