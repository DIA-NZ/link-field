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
use SilverStripe\Assets\File;

class WTLink extends DBComposite
{
    /**
         * @var string $getCurrency()
         */
    protected $type;
    protected $internal;
    protected $external;
    protected $email;
    protected $file;
    protected $targetBlank;

        /**
         * @var float $currencyAmount
         */
    protected $link;

    protected $isChanged = false;

        /**
         * @param array
         */
    private static $composite_db = array(
                "Type" => "Enum('Internal, External, Email, File', 'Internal')",
                "Internal" => 'Int',
                "External" => 'Varchar(255)',
                "Email" => 'Varchar(255)',
                "File" => 'Int',
                'TargetBlank' => 'Boolean'
        );

    public function compositeDatabaseFields()
    {
        return self::$composite_db;
    }

    public function isChanged()
    {
        return $this->isChanged;
    }

    public function setValue($value, $record = null, $markChanged = true)
    {
        if ($value instanceof WTLink && $value->exists()) {
            $this->setType($value->getType(), $markChanged);
            $this->setInternal($value->getInternal(), $markChanged);
            $this->setExternal($value->getExternal(), $markChanged);
            $this->setEmail($value->getEmail(), $markChanged);
            $this->setFile($value->getFile(), $markChanged);
            $this->setTargetBlank($value->getTargetBlank(), $markChanged);

            if ($markChanged) {
                $this->isChanged = true;
            }
        } elseif ($record && is_array($record) && isset($record[$this->name . 'Type'])) {
            if ($record[$this->name . 'Type']) {
                if (!empty($record[$this->name . 'Type'])) {
                    $this->setType($record[$this->name . 'Type'], $markChanged);
                } else {
                    $this->setType('internal', $markChanged);
                }

                if (isset($record[$this->name . 'Internal'])) {
                    $this->setInternal($record[$this->name . 'Internal'], $markChanged);
                }
                if (isset($record[$this->name . 'External'])) {
                    $this->setExternal($record[$this->name . 'External'], $markChanged);
                }
                if (isset($record[$this->name . 'Email'])) {
                    $this->setEmail($record[$this->name . 'Email'], $markChanged);
                }
                if (isset($record[$this->name . 'File'])) {
                    $this->setFile($record[$this->name . 'File'], $markChanged);
                }
                if (isset($record[$this->name . 'TargetBlank'])) {
                    $this->setTargetBlank($record[$this->name . 'TargetBlank'], $markChanged);
                }
            } else {
                $this->value = $this->nullValue();
            }
            if ($markChanged) {
                $this->isChanged = true;
            }
        } elseif (is_array($value)) {
            if (array_key_exists('Type', $value)) {
                $this->setType($value['Type'], $markChanged);
            }
            if (array_key_exists('Internal', $value)) {
                $this->setInternal($value['Internal'], $markChanged);
            }
            if (array_key_exists('Email', $value)) {
                $this->setEmail($value['Email'], $markChanged);
            }
            if (array_key_exists('File', $value)) {
                $this->setFile($value['File'], $markChanged);
            }
            if (array_key_exists('External', $value)) {
                $this->setExternal($value['External'], $markChanged);
            }
            if (array_key_exists('TargetBlank', $value)) {
                $this->setTargetBlank($value['TargetBlank'], $markChanged);
            }
            if ($markChanged) {
                $this->isChanged = true;
            }
        } else {
            // @todo Allow to reset a money value by passing in NULL
                //user_error('Invalid value in Money->setValue()', E_USER_ERROR);
        }
    }

    public function requireField()
    {
        $fields = $this->compositeDatabaseFields();
        if ($fields) {
            foreach ($fields as $name => $type) {
                DB::requireField($this->tableName, $this->name.$name, $type);
            }
        }
    }

    public function setType($value, $markChanged = true)
    {
        $this->type = $value;
        if ($markChanged) {
            $this->isChanged = true;
        }
    }

    public function getType()
    {
        return $this->type;
    }

    public function setInternal($value, $markChanged = true)
    {
        $this->internal = $value;
        if ($markChanged) {
            $this->isChanged = true;
        }
    }

    public function getInternal()
    {
        return $this->internal;
    }


    public function getExternal()
    {
        return $this->external;
    }

    public function setExternal($value, $markChanged = true)
    {
        if ($value && (stripos($value, 'http://') === false && stripos($value, 'https://') === false)) {
            $value = 'http://' . $value;
        }
        $this->external = $value;
        if ($markChanged) {
            $this->isChanged = true;
        }
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($value, $markChanged = true)
    {
        $this->email = $value;
        if ($markChanged) {
            $this->isChanged = true;
        }
    }


    public function setFile($value, $markChanged = true)
    {
        $this->file = $value;
        if ($markChanged) {
            $this->isChanged = true;
        }
    }

    public function getFile()
    {
        return $this->file;
    }

    public function setTargetBlank($value, $markChanged = true)
    {
        $this->targetBlank = $value;
        if ($markChanged) {
            $this->isChanged = true;
        }
    }

    public function getTargetBlank()
    {
        return $this->targetBlank;
    }

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

    public function writeToManipulation(&$manipulation)
    {
        if ($this->getType()) {
            $manipulation['fields'][$this->name.'Type'] = $this->prepValueForDB($this->getType());
        } else {
            $manipulation['fields'][$this->name.'Type'] =
                DBField::create_field('Varchar', $this->getType())->nullValue();
        }

        if ($this->getInternal()) {
            $manipulation['fields'][$this->name.'Internal'] = $this->getInternal();
        } else {
            $manipulation['fields'][$this->name.'Internal'] =
                DBField::create_field('Int', $this->getInternal())->nullValue();
        }

        if ($this->getExternal()) {
            $manipulation['fields'][$this->name.'External'] = $this->prepValueForDB($this->getExternal());
        } else {
            $manipulation['fields'][$this->name.'External'] =
                DBField::create_field('Varchar', $this->getExternal())->nullValue();
        }

        if ($this->getEmail()) {
            $manipulation['fields'][$this->name.'Email'] = $this->prepValueForDB($this->getEmail());
        } else {
            $manipulation['fields'][$this->name.'Email'] =
                DBField::create_field('Varchar', $this->getEmail())->nullValue();
        }

        if ($this->getFile()) {
            $manipulation['fields'][$this->name.'File'] = $this->getFile();
        } else {
            $manipulation['fields'][$this->name.'File'] =
                DBField::create_field('Int', $this->getFile())->nullValue();
        }

        if ($this->getTargetBlank()) {
            $manipulation['fields'][$this->name.'TargetBlank'] = $this->getTargetBlank();
        } else {
            $manipulation['fields'][$this->name.'TargetBlank'] =
                DBField::create_field('Int', $this->getTargetBlank())->nullValue();
        }
    }

    public function addToQuery(&$query)
    {
        //parent::addToQuery($query);
            $query->selectField(sprintf('"%sType"', $this->name));
        $query->selectField(sprintf('"%sInternal"', $this->name));
        $query->selectField(sprintf('"%sExternal"', $this->name));
        $query->selectField(sprintf('"%sEmail"', $this->name));
        $query->selectField(sprintf('"%sFile"', $this->name));
        $query->selectField(sprintf('"%sTargetBlank"', $this->name));
    }

    public function Link()
    {
        $link = '';
        switch ($this->type) {
            case 'Internal':
                if (SiteTree::get()->byID($this->internal) != null) {
                    if ($this->internal) {
                        $link = SiteTree::get()->byID($this->internal)->Link();
                    }
                }
                break;
            case 'External':
                    $link = $this->external;
                break;
            case 'Email':
                    $link = $this->email ? 'mailto:' . $this->email : '';
                break;
            case 'File':
                if ($this->file) {
                    $file = File::get()->byID($this->file);
                    if ($file) {
                        if (preg_match("/^Uploads\//", $file->Filename)) {
                            list($folder, $filename) = explode("/", $file->Filename);
                            $link = "assets/Uploads/" . substr($file->FileHash, 0, 10) . "/" . $filename;
                        } else {
                            $link = $file->Filename;
                        }
                    }
                }
        }

        return $link;
    }

    public function Tag()
    {
        $link = $this->Link();

        if ($link) {
            $target = !empty($this->targetBlank) ? 'target="_blank"' : '';

            return "<a href=\"{$link}\" {$target}>";
        }

        return '';
    }

    public function TagWithClass()
    {
        $link = $this->Link();

        if ($link) {
            $target = !empty($this->targetBlank) ? 'target="_blank"' : '';

            return "<a class=\"book wd2 pie\" href=\"{$link}\" {$target}>";
        }

        return '';
    }
}
