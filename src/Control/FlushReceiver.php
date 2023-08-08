<?php

namespace Sunnysideup\FlushFrontEnd\Control;

use Exception;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Core\Resettable;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Permission;
use Sunnysideup\FlushFrontEnd\Model\FlushRecord;

class FlushReceiver extends Controller
{
    private static $allowed_actions = [
        'do' => true,
        'completed' => true,
        'available' => true,
    ];

    public static function my_url_segment(): string
    {
        return 'flush-front-end';
    }

    public function Link($action = '')
    {
        return '/'.self::join_links(self::my_url_segment(), $action);
    }

    public function completed()
    {
        if (Director::is_cli() || Permission::check('ADMIN')) {
            $objects = FlushRecord::get()->filter(['Done' => false]);
            foreach($objects as $obj) {
                echo DBField::create_field('DateTime', $obj->LastEdited)->ago().' - '.$obj->Code;
                if(! Director::is_cli()) {
                    echo PHP_EOL;
                } else {
                    echo '<br />';
                }
            }
        } else {
            die('This needs to be run from the command line or you need to be logged in as ADMIN.');
        }
    }

    public function available()
    {
        if (Director::is_cli() || Permission::check('ADMIN')) {
            $objects = FlushRecord::get()->filter(['Done' => false]);
            foreach($objects as $obj) {
                if(Director::is_cli()) {
                    echo $this->Link('do/'.$obj->Code).PHP_EOL;
                } else {
                    echo '<a href="'.$this->Link('do/'.$obj->Code).'">'.$obj->Code.'</a><br />';
                }
            }
        } else {
            die('This needs to be run from the command line or you need to be logged in as ADMIN.');
        }
    }

    public function do($request)
    {
        if (Director::is_cli()) {
            die('This needs to be run from the front-end.');
        }
        $code = $request->param('ID');
        $obj = $this->getFlushRecord($code);
        if ($obj) {
            // mark as done first
            $obj->Done = true;
            $obj->write();

            $this->doFlush();
            $olds = FlushRecord::get()->filter(['Created:LessThan' => date('Y-m-d h:i:s', strtotime('-3 months'))]);
            foreach($olds as $old) {
                $old->delete();
            }

            return '
-----------------------------------------
SUCCESS: FRONT-END FLUSHED
-----------------------------------------

';
        }
        return '<br />ERROR';
    }

    protected function doFlush()
    {
        if (file_exists(TEMP_PATH)) {
            $this->deleteFolderContents(TEMP_PATH);
        }

        HTTPCacheControlMiddleware::singleton()->disableCache(true);
        ClassLoader::inst()->getManifest()->regenerate(false);
        // Reset all resettables
        /** @var Resettable $resettable */
        foreach (ClassInfo::implementorsOf(Resettable::class) as $resettable) {
            $resettable::reset();
        }

        /** @var Flushable $class */
        foreach (ClassInfo::implementorsOf(Flushable::class) as $class) {
            $class::flush();
        }
    }

    protected function getFlushRecord(string $code): ?FlushRecord
    {
        /** @var FlushRecord $obj */
        $obj = FlushRecord::get()->filter(['Done' => false, 'Code' => $code])->first();
        if ($obj) {
            return $obj;
        }

        echo 'object not found';

        return null;
    }

    // Function to delete files and folders recursively
    private function deleteFolderContents(string $folderPath)
    {
        if (! is_dir($folderPath)) {
            return;
        }

        $files = array_diff(scandir($folderPath), ['.', '..']);

        foreach ($files as $file) {
            $filePath = $folderPath . '/' . $file;

            if (is_dir($filePath)) {
                $this->deleteFolderContents((string) $filePath); // Recursively delete sub-folders
            } else {
                try {
                    unlink($filePath); // Delete files
                } catch (Exception $e) {
                }
            }
        }
    }
}
