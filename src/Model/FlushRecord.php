<?php

namespace Sunnysideup\FlushFrontEnd\Model;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Flushable;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Security;
use Sunnysideup\FlushFrontEnd\Control\FlushReceiver;

/**
 * Class \Sunnysideup\FlushFrontEnd\Model\FlushRecord
 *
 * @property ?string $Code
 * @property ?string $Response
 * @property bool $Done
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 * @mixin DataObjectExtension
 * @mixin FixBooleanSearchAsExtension
 */
class FlushRecord extends DataObject implements Flushable
{
    protected static $done = false;

    private static $table_name = 'FlushRecord';

    private static $db = [
        'Code' => 'Varchar',
        'Response' => 'Varchar',
        'Done' => 'Boolean',
    ];

    private static $summary_fields = [
        'Created.Nice' => 'When',
        'Code' => 'Code',
        'Done.Nice' => 'Done',
    ];

    private static $indexes = [
        'ID' => true,
        'Done' => true,
        'Code' => true,
    ];

    private static $default_sort = [
        'ID' => 'DESC',
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldsToTab(
            'Root.Main',
            [
                ReadonlyField::create(
                    'Created',
                    'When'
                ),
                ReadonlyField::create(
                    'Code',
                    'Code'
                ),
                ReadonlyField::create(
                    'Response',
                    'Response'
                ),
                CheckboxField::create(
                    'Done',
                    'Done'
                ),
            ]
        );

        return $fields;
    }

    public static function flush()
    {
        if (Security::database_is_ready() && Director::is_cli() && false === self::$done) {
            self::$done = true;
            register_shutdown_function(function () {
                $obj = \Sunnysideup\FlushFrontEnd\Model\FlushRecord::create();
                $obj->write();
                sleep(2);
                $code = $obj->Code;
                $url = Director::absoluteURL(
                    Controller::join_links(FlushReceiver::my_url_segment(), 'do', $code)
                );
                if (!Director::isLive()) {
                    $user = Environment::getEnv('SS_BASIC_AUTH_USER');
                    $pass = Environment::getEnv('SS_BASIC_AUTH_PASSWORD');
                    if ($user && $pass) {
                        $url = str_replace('://', '://' . $user . ':' . $pass . '@', $url);
                    }
                }
                DB::alteration_message('Creating flush link: ' . $url);
                // Create a new cURL resource
                $ch = curl_init();

                // Set the file URL to fetch through cURL
                curl_setopt($ch, CURLOPT_URL, $url);

                // Do not check the SSL certificates
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                // Return the actual result of the curl result instead of success code
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                $data = curl_exec($ch);
                curl_close($ch);

                $obj->Response = $data;
                $obj->write();
                echo $data;
            });
        }
    }

    public static function run_flush($url) {}

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $hex = bin2hex(random_bytes(18));
        $code = serialize($hex);
        $code = preg_replace('#[^a-zA-Z0-9]+#', '', $code);

        $this->Code = $code;
    }
}
