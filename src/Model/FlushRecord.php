<?php

namespace Sunnysideup\FlushFrontEnd\Model;

use Exception;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Flushable;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Security;
use Sunnysideup\FlushFrontEnd\Control\FlushReceiver;

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
        'Created' => true,
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

    // Function to delete files and folders recursively
    public function deleteFolderContents(string $folderPath)
    {
        if (!is_dir($folderPath)) {
            return;
        }

        $files = array_diff(scandir($folderPath), array('.', '..'));

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

    public static function run_flush($url)
    {
    }

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $hex = bin2hex(random_bytes(18));
        $code = serialize($hex);
        $code = preg_replace('#[^a-zA-Z0-9]+#', '', $code);

        $this->Code = $code;
    }
}
