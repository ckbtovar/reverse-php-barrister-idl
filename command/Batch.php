<?
namespace CreditKarma\Barrister\Tools\Idl2Php\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Batch extends Command {

    protected function configure() {
        $this
            ->setName('batch')
            ->setDescription('Batch process IDL JSON to PHP classes using mapping file');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln('Done');
    }
}
