<?

class IdlParser {

    const INDENT = '    ';
    const PHP_OPEN_TAG = "<?php";

    private $json;
    private $package;
    private $outputDir;
    private $version;
    private $saveCount;

    public function __construct($json, $package, $outputDir, $version) {
        $this->json = $json;
        $this->package = $package;
        $this->outputDir = $outputDir;
        $this->version = $version;
    }

    public function getVersion() {
        return $this->version;
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
                $file = $this->addAutogenerateComment($file);
                $this->save($entity, $file);
            }
        }
    }

    private function processEnum($entity) {
        $codeStr = $this->formatNamespace();
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
        $codeStr = $this->formatNamespace();
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
        $codeStr = $this->formatNamespace();
        $codeStr .= $this->formatComment($entity->comment);
        $codeStr .= "interface " . $entity->name . " {\n\n";
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
            $codeStr .= ");\n\n";
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

    private function addAutoGenerateComment($file) {
        $comment = self::PHP_OPEN_TAG . "
/**
 * Autogenerated by Idl-json-to-php.php (" . $this->getVersion() . ")
 *
 * DO NOT EDIT UNLESS YOU ARE SURE THAT YOU KNOW WHAT YOU ARE DOING
 */\n\n";
        return $comment . $file;
    }
}