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

class FlushReceiver extends Controller
{
    private static $allowed_actions = [
        'do' => true,
    ];

    public function do($request)
    {
        $code = $request->param('ID');
        if($this->isSafe($code)) {
            Injector::inst()->get(Kernel::class)->boot(true);
            $this->get('/');
            $this->doFlush();
            //run flush!
            $obj->Done = true;
            $obj->write();
        }
    }

    protected function doFlush()
    {
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

    protected function isSafe(string $code) : bool
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
