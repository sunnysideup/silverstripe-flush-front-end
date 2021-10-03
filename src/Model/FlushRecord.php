<?php

namespace Sunnysideup\FlushFrontEnd\Model;

use SilverStripe\Forms\ReadonlyField;



class FlushRecord extends DataObject implements flushable
{

    private static $db = [
        'FlushCode' => 'Varchar',
        'Done' => 'Boolean',
    ];

    private static $summary_fields = [
        'Created.Nice' => 'When',
        'FlushCodee' => 'Code',
    ];

    private static $indexes = [
        'Created' => true,
    ];

    private static $default_sort = [
        'Created' => 'DESC',
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
                    'FlushCode',
                    'Code'
                ),
                CheckboxField::create(
                    'Done',
                    'Done'
                )
            ]
        );
        return $fields;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $hex = bin2hex(random_bytes(18));
        $code = serialize($hex);
        $this->Code = $code;
    }

    public static function flush()
    {
        if(Director::isCli()) {
            parent::requireDefaultRecords();
            $obj = self::create();
            $obj->write();
            $code = $obj->code;
            $url = Director::AbsoluteLink(
                Controller::join_link(FlushReceiver::my_url_segment(),  $code)
            );
            DB::alteration_message('Creating flush link: '.$url);
            register_shutdown_function(function () {
                Sunnysideup\FlushFrontEnd\Model\FlushRecord::run_flush($url);
            });
        }
    }

    public static function run_flush($url)
    {
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

        return $data;
    }

}
