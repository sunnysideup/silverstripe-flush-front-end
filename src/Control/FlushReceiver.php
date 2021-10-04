<?php

namespace Sunnysideup\FlushFrontEnd\Control;


use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\Util\IPUtils;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Security\MemberAuthenticator\CookieAuthenticationHandler;
use SilverStripe\Security\Security;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Core\Resettable;
use SilverStripe\Core\Flushable;
use SilverStripe\ORM\DataObject;

use Sunnysideup\FlushFrontEnd\Model\FlushRecord;


class FlushReceiver extends Controller
{
    private static $allowed_actions = [
        'do' => true,
    ];

    public static function my_url_segment() : string
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
        if($this->isValid($code)) {

            $this->doFlush();

            //run flush!
            $obj->Done = true;
            $obj->write();
        }
    }

    protected function doFlush()
    {
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

    protected function isValid(string $code) : bool
    {
        $obj = DataObject::get_one(FlushRecord::class);
        if($obj) {
            if($obj->Code === $code) {
                if((bool) $obj->Done === false) {
                    return true;
                }
            }
        }

        return false;
    }

}
