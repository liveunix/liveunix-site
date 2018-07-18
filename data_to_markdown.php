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
        } elseif (substr($distro_info->getFilename(), -4) != 'yaml' || substr($distro_info->getFilename(), 0, 6) == 'sample') {
            continue;
        }
        $filename = "unixdata/" . $fileinfo->getFilename()
                . "/" . $distro_info->getFilename();
        $distro_name = substr($distro_info->getFilename(), 0, -5);
        $file = file_get_contents($filename);
        $file = strtolower($file);
        $data[$distro_name] = yaml_parse($file);
    }
}

foreach ($data as $key => $distro) {
    $page = new Markdown($distro['name'], $distro['summary']);
    $page->writeAsciiFromFile($key);
    $page->writeLine("home < " . $distro['homepage'] . " >");
    $page->writeLine("summary < ". $distro['summary'] . " >");

    if (!empty($distro['description'])) {
        $page->writeLine("description < " . $distro['description'] .  " >");
    }

    $page->writeLine("degree of difficulty < " . $distro['difficulty'] .  " >");

    if (!empty($distro['docs'])) {
        $page->writeLine("documentation < " . $distro['docs'] .  " >");
    }

    if (!empty($distro['wiki'])) {
        $page->writeLine("wiki < " . $distro['wiki'] .  " >");
    }

    $page->writeStringList($distro['arch'], "arch < ", " >");

    if (!empty($distro['latest_version'])) {
        $page->writeLine("latest version < " . $distro['latest_version'] .  " >");
    }

    if (!empty($distro['based_on'])) {
        $page->writeLine("based on < " . $distro['based_on'] .  " >");
    }

    $page->writeStringList($distro['release_model'], "release < ", " >");

    if (!empty($distro['installation'])) {
        $page->writeStringList($distro['installation'], "installation < ", " >");
    }

    if (!empty($distro['default_userspace'])) {
        $page->writeLine("default desktop < " . $distro['default_userspace'] .  " >");
    }

    if (!empty($distro['flavours'])) {
        $page->writeStringList($distro['flavours'], "flavours < ", " >");
    }

    if (!empty($distro['package_manager'])) {
        $page->writeStringList($distro['package_manager'], "package manager < ", " >");
    }

    if (!empty($distro['init'])) {
        $page->writeStringList($distro['init'], "init < ", " >");
    }

    if (!empty($distro['libc'])) {
        $page->writeStringList($distro['libc'], "libc < ", " >");
    }

    if (!empty($distro['bugtracker'])) {
        $page->writeLine("bugtracker < " . $distro['bugtracker'] .  " >");
    }

    if (!empty($distro['git'])) {
        $page->writeLine("git < " . $distro['git'] .  " >");
    }

    if (!empty($distro['mailing_lists'])) {
        $page->writeLine("mailing_lists < " . $distro['mailing_lists'] .  " >");
    }

    if (!empty($distro['forum'])) {
        $page->writeLine("forum < " . $distro['forum'] .  " >");
    }

    if (!empty($distro['packages'])) {
        $page->writeLine("packages < " . $distro['packages'] .  " >");
    }

    if (!empty($distro['blog'])) {
        $page->writeLine("blog < " . $distro['blog'] .  " >");
    }

    if (!empty($distro['iso'])) {
        if (!empty($distro['flavours'])) {
            foreach ($distro['iso'] as $arch => $data) {
                $page->writeVersionTable($arch, $data, $distro['flavours']);
            }
        } else {
            foreach ($distro['arch'] as $arch) {
                if (!empty($distro['iso'][$arch])) {
                    $page->writeLinksList("iso.$arch", $distro['iso'][$arch]);
                }
            }
        }
    }

    if (!empty($distro['stage3'])) {
        if (!empty($distro['profiles'])) {
            foreach ($distro['arch'] as $arch => $data) {
                $page->writeVersionTable($arch, $data, $distro['profiles']);
            }
        } else {
            foreach ($distro['arch'] as $arch) {
                if (!empty($distro['stage3'][$arch])) {
                    $page->writeLinksList("stage3.$arch", $distro['stage3'][$arch]);
                }
            }
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

    public function __construct(string $title, string $summary) {
        $this->_page .= "---\ntitle: $title \ndescription: $summary\ntype: \"data\"\n---\n\n";
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

    public function writeLinksList(string $name, array $list) {
        $this->_page .= "$name = [\n";
        $t = "";
        foreach ($list as $key => $value) {
            $t .= ",[$key]($value)\n";
        }
        $this->_page .= substr($t, 1);

        $this->_page .= "]\n\n";
    }

    public function writeStringList(array $list, string $start = "", string $end = "") {
        $line = "";
        foreach ($list as $element) {
            $line .= ", $element";
        }
        $line = "$start" . substr($line, 1) . "$end";
        $this->writeLine($line);
    }
}

