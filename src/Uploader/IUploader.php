<?php

namespace Ngscz\Elfinder\Uploader;

interface IUploader
{
    function onUpload($cmd, $result, $args, $elfinder);

    function onRename($cmd, $result, $args, $elfinder);

    function onRemove($cmd, $result, $args, $elfinder);

    function onFileDimension($cmd, $result, $args, $elfinder);
}