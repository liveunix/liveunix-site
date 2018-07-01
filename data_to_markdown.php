<?php

$data = [];

function delTree($dir) {
   $files = array_diff(scandir($dir), array('.','..'));
    foreach ($files as $file) {
      (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}

if (is_dir('content/data')) {
    delTree('content/data');
}
mkdir('content/data');

if (is_dir('static/ascii')) {
    delTree('static/ascii');
}
mkdir('static/ascii');

$data_dir = new DirectoryIterator('unixdata');
foreach ($data_dir as $fileinfo) {
    if (!$fileinfo->isDir() || $fileinfo->getFilename() == '..') {
        continue;
    }
    $distro_dir = new DirectoryIterator("unixdata/" . $fileinfo->getFilename());
    foreach ($distro_dir as $distro_info) {
        if (!$distro_info->isFile()) {
            continue;
        } elseif ($distro_info->getFilename() === 'ascii') {
            file_put_contents("static/ascii/" . $fileinfo->getFilename(), file_get_contents("unixdata/" . $fileinfo->getFilename() . "/ascii"));
            continue;
        } elseif (substr($distro_info->getFilename(), -4) != 'yaml') {
            continue;
        }
        $filename = "unixdata/" . $fileinfo->getFilename()
                . "/" . $distro_info->getFilename();
        $distro_name = substr($distro_info->getFilename(), 0, -5);
        $data[$distro_name] = yaml_parse_file($filename);

    }
}

foreach ($data as $key => $distro) {
    $page = new Markdown($distro['name'], $distro['description']);
    $page->writeAsciiFromFile($key);
    $page->writeLine($distro['homepage']);
    $page->writeLine($distro['description']);

    $page->writeStringList($distro['arch']);
    $page->writeStringList($distro['release_model']);
    if (!empty($distro['flavours'])) {
        $page->writeStringList($distro['flavours']);
    }

    if (!empty($distro['iso'])) {
        if (!empty($distro['flavours'])) {
            foreach ($distro['iso'] as $arch => $data) {
                $page->writeVersionTable($arch, $data, $distro['flavours']);
            }
        } else {
            $page->writeLinksList($distro['iso']);
        }
    }

    if (!empty($distro['stage3'])) {
        if (!empty($distro['profiles'])) {
            foreach ($distro['stage3'] as $arch => $data) {
                $page->writeVersionTable($arch, $data, $distro['profiles']);
            }
        } else {
            $page->writeLinksList($distro['stage3']);
        }
    }

    $output_filename = "content/data/" . $key;
    $output_filename .= '.md';
    $page->writeToFile($output_filename);

}

/** Class Markdown **/
class Markdown
{
    private $_page;

    public function __construct(string $title, string $description) {
        $this->_page .= "---\ntitle: $title \ndescription: $description\ntype: \"data\"\n---\n\n";
    }

    public function writeLine(string $line) {
        $this->_page .= "$line\n\n";
    }

    public function writeAsciiFromFile(string $name) {
        $this->_page .= "<div class=\"ascii-art\">\n<script>\n document.write(loadFile(\"/ascii/$name\"));\n</script>\n</div>\n\n";
    }

    public function writeToFile(string $path) {
        file_put_contents($path, $this->_page);
    }

    public function writeVersionTable(string $arch, array $matrix, $flavours) {
        $this->_page .= "|$arch|";
        $i = 0;
        foreach ($matrix as $version => $data) {
            $this->_page .= "$version|";
            $i++;
        }
        $this->_page .= "\n|";
        for ($k = 0; $k <= $i; $k++) {
            $this->_page .= "-----|";
        }
        $this->_page .= "\n";
        foreach ($flavours as $flavour) {
            $this->_page .= "|$flavour|";
            foreach ($matrix as $version => $data) {
                if (!empty($data[$flavour])) {
                    $this->_page .= "[wget](" . $data[$flavour] . ")|";
                } else {
                    $this->_page .= "|";
                }
            }
            $this->_page .= "\n";
        }
    }

    public function writeLinksList(array $list) {
        foreach ($list as $key => $value) {
            $this->_page .= "- [$key]($value)\n";
        }

        $this->_page .= "\n\n";
    }

    public function writeStringList(array $list) {
        $line = "";
        foreach ($list as $element) {
            $line .= ", $element";
        }
        $this->writeLine(substr($line, 1));
    }
}

