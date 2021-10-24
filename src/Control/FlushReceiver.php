<?php

namespace Sunnysideup\FlushFrontEnd\Control;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Core\Resettable;
use Sunnysideup\FlushFrontEnd\Model\FlushRecord;

class FlushReceiver extends Controller
{
    private static $allowed_actions = [
        'do' => true,
    ];

    public static function my_url_segment(): string
    {
        return '/admin/flush-front-end/';
    }

    public function Link($action = '')
    {
        return self::join_links(self::my_url_segment(), $action);
    }

    public function do($request)
    {
        $code = $request->param('ID');
        $obj = $this->getFlushRecord($code);
        if ($obj) {
            $this->doFlush();

            //run flush!
            $obj->Done = true;
            $obj->write();
            echo 'FRONT-END FLUSHED';
        } else {
            echo '<br />ERROR';
        }
    }

    protected function doFlush()
    {
        if (Director::is_cli()) {
            die('This needs to be run from the front-end.');
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
        $obj = FlushRecord::get()->filter(['Done' => false, 'Code' => $code])->first();
        if ($obj) {
            return $obj;
        }
        echo 'object not found';

        return null;
    }
}
