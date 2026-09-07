<?php
$dirs = ['backoffice', 'cpdth'];
foreach ($dirs as $d) {
    $path = 'c:/xampp/htdocs/am/' . $d;
    if (!is_dir($path)) continue;
    $dirIt = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
    $filter = new RecursiveCallbackFilterIterator($dirIt, function ($current, $key, $iterator) {
        $name = $current->getFilename();
        if ($current->isDir()) {
            return !in_array($name, ['vendor', 'node_modules', 'upload', '.git', 'tmp', 'brain']);
        }
        return true;
    });
    $it = new RecursiveIteratorIterator($filter);
    foreach ($it as $f) {
        if ($f->isFile() && preg_match('/\.(php|js|html)$/', $f->getFilename())) {
            $cnt = @file_get_contents($f->getPathname());
            if ($cnt && (stripos($cnt, 'lesson_video') !== false || stripos($cnt, 'vimeo') !== false)) {
                echo $f->getPathname() . PHP_EOL;
            }
        }
    }
}
