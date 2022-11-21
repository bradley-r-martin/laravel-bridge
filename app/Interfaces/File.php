<?php

namespace LaravelBridge\Interfaces;

use GuzzleHttp\Psr7\MimeType;
use Illuminate\Support\Facades\Storage;

class File implements \JsonSerializable
{
    public $key;

    public $name;

    public $uuid;

    public $bucket;

    public $content_type;

    public $extension;

    public $size;

    public $meta;

    public $status;

    public function __construct($data = null)
    {
        if ($data) {
            $this->key = optional($data)->{'key'};
            $this->name = optional($data)->{'name'};
            $this->uuid = optional($data)->{'uuid'};
            $this->bucket = optional($data)->{'bucket'};
            $this->content_type = optional($data)->{'content_type'};
            $this->extension = optional($data)->{'extension'};
            $this->size = optional($data)->{'size'};
            $this->meta = optional($data)->{'meta'};
            $this->status = optional($data)->{'status'} ?? 'staged';
        }

        return $this;
    }

    public function sync($puragble)
    {
        if ($this->status === 'staged') {
            if ($this->persist(true)) {
                if ($puragble) {
                    if ($puragble->purge()) {
                        return true;
                    }
                    // New upload but couldnt remove old file.
                    return true;
                }

                return true;
            } else {
                // failed
                return false;
            }
        }
    }

    public function url()
    {
        if ($this->key) {
            $file = config('bridge.directories.'.$this->status).'/'.$this->uuid;
            $url = Storage::temporaryUrl($file, now()->addMinutes(59));

            return  $url;
        }

        return null;
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'uuid' => $this->uuid,
            'bucket' => $this->bucket,
            'content_type' => $this->content_type,
            'extension' => $this->extension,
            'size' => $this->size,
            'meta' => $this->meta,
            'status' => $this->status ?? 'staged',
            'url' => $this->url(),
        ];
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function __serialize()
    {
        return $this->toArray();
    }

    public function __toString()
    {
        return json_encode($this->toArray());
    }

    protected function folder()
    {
        switch($this->status) {
            case 'staged':
                return config('bridge.directories.staging');
            case 'persisted':
                return config('bridge.directories.persisted');
        }
    }

    protected function getDimensions()
    {
        $contents = Storage::get($this->folder().'/'.$this->uuid);
        $im = imagecreatefromstring($contents);
        $width = imagesx($im) ?? null;
        $height = imagesy($im) ?? null;

        return ['height' => $height, 'width' => $width];
    }

    public function persist($unmetered = false, $user = null)
    {
        if ($this->uuid && $this->status === 'staged') {
            if (! $unmetered) {
                $user = $user ?? auth()->user();
                if (Storage::size($this->folder().'/'.$this->uuid) >
                    (optional(optional($user)->storage())['available'] ?? 0)
                ) {
                    return false;
                }
            }

            $exists = Storage::exists($this->folder().'/'.$this->uuid);

            if ($exists) {
                $move = Storage::move($this->folder().'/'.$this->uuid, config('bridge.directories.persisted').'/'.$this->uuid);
                if ($move) {
                    $this->status = 'persisted';

                    // set meta data.
                    $meta = [];
                    $size = Storage::size($this->folder().'/'.$this->uuid);
                    $meta['last_modified'] = Storage::lastModified($this->folder().'/'.$this->uuid);
                    $mime = (new MimeType())->fromFilename($this->name);

                    switch ($mime) {
                        case 'image/gif':
                        case 'image/jpeg':
                        case 'image/png':
                            $meta['dimensions'] = $this->getDimensions();
                            $meta['orientation'] = $meta['dimensions']['width'] > $meta['dimensions']['height'] ? 'landscape' : 'portrait';
                            break;
                    }

                    $this->meta = (object) $meta;
                    $this->size = $size;
                    $this->mime = $mime;
                    $this->extension = pathinfo($this->name, PATHINFO_EXTENSION);

                    return true;
                } else {
                    // Couldnt move file
                    return false;
                }
            } else {
                // file missing from expected location
                return false;
            }

            return false;
        }

        return true;
    }

    public function stage()
    {
        try {
            if (Storage::copy($this->folder().'/'.$this->uuid, config('bridge.directories.staging').'/'.$this->uuid)) {
                $this->status = 'staged';

                return true;
            }
        } catch (\Exception $e) {
        }

        return false;
    }

    public function unarchive()
    {
        try {
            if (Storage::copy(config('bridge.directories.archived').'/'.$this->uuid, config('bridge.directories.persisted').'/'.$this->uuid)) {
                $this->status = 'persisted';

                return true;
            }
        } catch (\Exception $e) {
        }

        return false;
    }

    public function purge()
    {
        $directory = config('bridge.directories.'.$this->status);
        try {
            if (Storage::delete($directory.'/'.$this->uuid)) {
                $this->key = null;
                $this->name = null;
                $this->uuid = null;
                $this->bucket = null;
                $this->content_type = null;
                $this->extension = null;
                $this->size = null;
                $this->meta = null;
                $this->status = null;
            }
        } catch (\Exception $e) {
        }

        return false;
    }
}
