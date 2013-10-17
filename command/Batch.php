<?
namespace CreditKarma\Barrister\Tools\Idl2Php\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;



class Batch extends Command {

    protected function configure() {
        $this
            ->setName('batch')
            ->setDescription('Batch process IDL JSON to PHP classes using namespaced mapping file')
            ->setDefinition(array(
                new InputArgument('classmap', InputArgument::REQUIRED, 'JSON mapping file. Describes object mapping
                between input IDL JSON files to namespace and output dir.')
            ));
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $classmap = $input->getArgument('classmap');
        $classmapFile = file_get_contents($classmap);
        $batchJob = json_decode($classmapFile);

        $validator = new \Json\Validator('idl-json-map.json');
        $validator->validate($batchJob);

        foreach($batchJob as $job) {
            $command = $this->getApplication()->find('make');

            $arguments = array(
                'command' => 'make',
                'json'    => $job->json,
                'output'  => $job->output,
                'package' => $job->package
            );

            $input = new ArrayInput($arguments);
            $command->run($input, $output);
        }

        $output->writeln('Done');
    }
}
