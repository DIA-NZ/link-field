<?php

namespace Dia\SilverStripe\LinkField;

use SilverStripe\Forms\TextField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\View\Requirements;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBComposite;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Director;
use SilverStripe\Assets\File;

class WTLink extends DBComposite
{
    /**
     * @var boolean
     */
    protected $isChanged = false;

    /**
     * @param array
     */
    private static $composite_db = [
        "Type" => "Enum('Internal, External, Email, File', 'Internal')",
        "Internal" => 'Int',
        "External" => 'Varchar(255)',
        "Email" => 'Varchar(255)',
        "File" => 'Int',
        'TargetBlank' => 'Boolean'
    ];

    /**
     * @return int
     */
    public function exists()
    {
        return ($this->getType());
    }

    /**
     * Returns a CompositeField instance used as a default
     * for form scaffolding.
     *
     * Used by {@link SearchContext}, {@link ModelAdmin}, {@link DataObject::scaffoldFormFields()}
     *
     * @param string $title Optional. Localized title of the generated instance
     * @return FormField
     */
    public function scaffoldFormField($title = null, $params = null)
    {
        $field = new WTLinkField($this->name);

        return $field;
    }

    /**
     * @return string
     */
    public function Link()
    {
        switch ($this->Type) {
            case 'Internal':
                if (!$this->Internal) {
                    return false;
                }

                $page = SiteTree::get()->byID($this->Internal);

                if ($page) {
                    return $page->Link();
                }
            case 'External':
                return $this->dbObject('External')->ATT();
            case 'Email':
                return $this->Email ? 'mailto:' . $this->dbObject('Email')->ATT() : '';
            case 'File':
                if (!$this->File) {
                    return false;
                }

                $file = File::get()->byID($this->file);

                if ($file) {
                    return $file->Link();
                }
        }

        return false;
    }

    /**
     * @return string
     */
    public function AbsoluteLink()
    {
        return Director::absoluteURL($this->Link());
    }

    /**
     * @return SilverStripe\ORM\FieldType\DBHTMLText
     */
    public function Tag()
    {
        $link = $this->Link();

        if ($link) {
            $target = !empty($this->TargetBlank) ? 'target="_blank"' : '';
            $link = "<a href=\"{$link}\" {$target}>";
        }

        return  DBField::create_field('HTMLText', $link);
    }

    /**
     * @return SilverStripe\ORM\FieldType\DBHTMLText
     */
    public function TagWithClass()
    {
        $link = $this->Link();

        if ($link) {
            $target = !empty($this->TargetBlank) ? 'target="_blank"' : '';
            $link = "<a class=\"book wd2 pie\" href=\"{$link}\" {$target}>";
        }

        return DBField::create_field('HTMLText', $link);
    }
}
