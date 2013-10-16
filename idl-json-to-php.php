<?php
require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

$console = new Application();
$console
    ->register('make')
    ->setDefinition(array(
        new InputArgument('json', InputArgument::REQUIRED, 'IDL JSON file'),
        new InputArgument('output', InputArgument::REQUIRED, 'Output directory'),
        new InputArgument('package', InputArgument::OPTIONAL, 'Package name')
    ))
    ->setDescription('Converts IDL JSON file into PHP stub classes')
    ->setCode(function (InputInterface $input, OutputInterface $output) {
        $package = $input->getArgument('package');
        $json = $input->getArgument('json');
        $outputDir = $input->getArgument('output');

        if (strpos($package, '.')) {
            $package = str_replace('.', "\\", $package);
        }

        $jsonData = json_decode(file_get_contents($json));

        $parser = new IdlParser($jsonData, $package, $outputDir);
        $output->writeln(sprintf('Generating code for <info>%s</info> to <info>%s</info>', $json, $outputDir));
        $parser->parse();
    });

$console->run();


class IdlParser {

    const INDENT = '    ';

    private $json;
    private $package;
    private $outputDir;
    private $saveCount;

    public function __construct($json, $package, $outputDir) {
        $this->json = $json;
        $this->package = $package;
        $this->outputDir = $outputDir;
    }

    public function parse() {
        foreach ($this->json as $entity) {
            $file = false;
            switch ($entity->type) {
                case 'enum':
                    $file = $this->processEnum($entity);
                    break;
                case 'struct':
                    $file = $this->processStruct($entity);
                    break;
                case 'interface':
                    $file = $this->processInterface($entity);
                    break;
                default:
                    break; // do nothing
            }
            if ($file) {
                $this->save($entity, $file);
            }
        }
    }

    private function processEnum($entity) {
        $codeStr = "<?php\n\n";
        $codeStr .= $this->formatNamespace();
        $codeStr .= $this->formatComment($entity->comment);
        $codeStr .= "class " . $entity->name . " {\n";
        foreach ($entity->values as $evp) {
            $comment = trim($evp->comment);
            if (!empty($comment)) {
                $codeStr .= self::INDENT . "/**\n" . self::INDENT . " * " . implode("\n" . self::INDENT . ' * ', explode("\n", $comment)) . "\n" . self::INDENT . " */\n";
            }
            $codeStr .= self::INDENT . "const ". $evp->value ." = '". $evp->value ."';\n";
        }
        $codeStr .= "}\n";
        return $codeStr;
    }

    private function processStruct($entity) {
        $codeStr = "<?php\n\n";
        $codeStr .= $this->formatNamespace();
        $codeStr .= $this->formatComment($entity->comment);
        $codeStr .= "class " . $entity->name . ($entity->extends ? ' extends ' . $entity->extends : '') . " {\n\n";
        foreach ($entity->fields as $field) {
            $comment = trim($field->comment);
            if (!empty($comment)) {
                $comment = ' ' . trim(str_replace(array("\r\n", "\r", "\n"), ' ', $comment));
            }
            $codeStr .= self::INDENT . "/** @var " . ($field->optional ? 'null|' : '') . $field->type . ($field->is_array ? '[]' : '') . $comment . " */\n";
            $codeStr .= self::INDENT . "public $" . $field->name . ";\n\n";
        }
        $codeStr .= "}\n";
        return $codeStr;
    }

    private function processInterface($entity) {
        $codeStr = "<?php\n\n";
        $codeStr .= $this->formatNamespace();
        $codeStr .= $this->formatComment($entity->comment);
        $codeStr .= "class " . $entity->name . " {\n\n";
        foreach ($entity->functions as $function) {
            $comment = trim($function->comment);

            // @param
            $params = '';
            foreach ($function->params as $param) {
                $params .= "@var " . $param->type . ($param->is_array ? '[]' : '') . ' $' . $param->name . "\n";
            }
            if (!empty($params)) {
                $comment .= "\n\n" . $params . "\n";
            }

            // @return
            $comment .= "@return " . ($function->returns->optional ? 'null|' : '') . $function->returns->type . ($function->returns->is_array ? '[]' : '') . "\n";

            // docblock
            if (!empty($comment)) {
                $codeStr .= self::INDENT . "/**\n" . self::INDENT . " * " . implode("\n" . self::INDENT . ' * ', explode("\n", trim($comment))) . "\n" . self::INDENT . " */\n";
            }

            // function
            $codeStr .= self::INDENT . "public function " . $function->name . "(";
            foreach ($function->params as $param) {
                if ($param->is_array) {
                    $codeStr .= 'array ';
                }
                elseif (!in_array($param->type, array('string', 'int', 'float', 'bool'))) {
                    // type checking works for classes but not basic types :(
                    $codeStr .= $param->type . ' ';
                }
                $codeStr .= "$" . $param->name . ", ";
            }
            // clear last param
            if (count($function->params)) {
                $codeStr = substr($codeStr, 0, -2);
            }
            $codeStr .= ") {}\n\n";
        }
        $codeStr .= "}\n";
        return $codeStr;
    }

    private function formatComment($comment) {
        $codeStr = '';
        $comment = trim($comment);
        if (!empty($comment)) {
            $codeStr .= "/**\n * " . implode(' * ', explode("\n", $comment)) . "\n */\n";
        }
        return $codeStr;
    }

    private function formatNamespace() {
        $codeStr = '';
        if (!empty($this->package)) {
            $codeStr = 'namespace ' . $this->package . ";\n\n";
        }
        return $codeStr;
    }

    private function save($entity, $file) {
        $dir = $this->outputDir;
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $filename = $dir . DIRECTORY_SEPARATOR . $entity->name . '.php';
        if (file_put_contents($filename, $file)) {
            $this->saveCount++;
        }
    }
}