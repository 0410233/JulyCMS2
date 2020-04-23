<?php

namespace App\Models;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class Media
{
    protected $disk;

    public function __construct()
    {
        $this->disk = Storage::disk('media');
    }

    public function under($path)
    {
        $folders = [];
        foreach ($this->disk->directories($path) as $dir) {
            $info = $this->dirInfo($dir);
            if ($info['name'] !== '.thumbs') {
                $folders[] = $info;
            }
        }

        $files = [];
        foreach ($this->disk->files($path) as $file) {
            $files[] = $this->fileInfo($file);
        }

        return compact('folders', 'files');
    }

    public function dirInfo($dir)
    {
        return [
            'name' => basename($dir),
            'mimeType' => 'folder',
        ];
    }

    public function fileInfo($file)
    {
        return [
            'name'         => basename($file),
            'mimeType'     => $this->disk->mimeType($file),
            'size'         => $this->disk->size($file),
            'modified'     => $this->disk->lastModified($file),
            'thumb'     => $this->disk->exists(dirname($file).'/.thumbs/'.basename($file)),
        ];
    }
}