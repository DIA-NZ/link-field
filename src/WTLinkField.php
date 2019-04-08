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

class WTLinkField extends TextField
{
    private static $url_handlers = [
                '$Action!/$ID' => '$Action'
        ];

    private static $allowed_actions = [
                'tree',
                'InternalTree',
                'FileTree'
        ];

    protected $fieldField = null;
    
    protected $internalField = null;
    
    protected $externalField = null;
    
    protected $emailField = null;
    
    protected $fileField = null;
    
    protected $anchorField = null;
    
    protected $targetBlankField = null;

        /**
         * @var FormField
         */
    protected $fieldType = null;

        /**
         * @var FormField
         */
    protected $fieldLink = null;

    public function __construct($name, $title = null, $value = "")
    {
        // naming with underscores to prevent values from actually being saved somewhere
            $this->fieldType =new OptionsetField(
                "{$name}[Type]",
                _t('HtmlEditorField.LINKTO', 'Link to'),
                array(
                            'Internal' => _t('HtmlEditorField.LINKINTERNAL', 'Page on the site'),
                            'External' => _t('HtmlEditorField.LINKEXTERNAL', 'Another website'),
                            //'Anchor' => _t('HtmlEditorField.LINKANCHOR', 'Anchor on this page'),
                            'Email' => _t('HtmlEditorField.LINKEMAIL', 'Email address'),
                            'File' => _t('HtmlEditorField.LINKFILE', 'Download a file'),
                    ),
                'Internal'
            );
        $this->fieldLink = new CompositeField(
            $this->internalField = new WTTreeDropdownField(
                "{$name}[Internal]",
                _t('HtmlEditorField.Internal', 'Internal'),
                'SilverStripe\CMS\Model\SiteTree',
                'ID',
                'Title',
                true
            ),
            $this->externalField = new TextField(
                "{$name}[External]",
                _t('HtmlEditorField.URL', 'External URL'),
                'http://'
            ),
            $this->emailField = new EmailField("{$name}[Email]", _t('HtmlEditorField.EMAIL', 'Email address')),
            $this->fileField = new WTTreeDropdownField(
                "{$name}[File]",
                _t('HtmlEditorField.FILE', 'File'),
                'SilverStripe\Assets\File',
                'ID',
                'Title',
                true
            ),
            $this->anchorField = new TextField("{$name}[Anchor]", 'Anchor (optional)'),
            $this->targetBlankField = new CheckboxField(
                "{$name}[TargetBlank]",
                _t(
                    'HtmlEditorField.LINKOPENNEWWIN',
                    'Open link in a new window?'
                )
            )
        );

        $this->anchorField->addExtraClass('no-hide');
        $this->targetBlankField->addExtraClass('no-hide');

        parent::__construct($name, $title, $value);
    }

    public function FileTree(HTTPRequest $request)
    {
        return $this->fileField->tree($request);
    }

    public function InternalTree(HTTPRequest $request)
    {
        return $this->internalField->tree($request);
    }

        /**
         * @return string
         */
    public function Field($properties = array())
    {
        Requirements::javascript('dia-nz/link-field:javascript/WTLinkField.js');
        return "<div class=\"fieldgroup\">" .
                    "<div class=\"fieldgroupField\">" . $this->fieldType->FieldHolder() . "</div>" .
                    "<div class=\"fieldgroupField\">" . $this->fieldLink->FieldHolder() . "</div>" .
                    "</div>";
    }

    public function setForm($form)
    {
        parent::setForm($form);

        if (isset($this->fileField)) {
            $this->fileField->setForm($form);
        }
        if (isset($this->internalField)) {
            $this->internalField->setForm($form);
        }
    }

        /**
         * 30/06/2009 - Enhancement:
         * SaveInto checks if set-methods are available and use them
         * instead of setting the values in the money class directly. saveInto
         * initiates a new Money class object to pass through the values to the setter
         * method.
         *
         */
    public function saveInto(\SilverStripe\ORM\DataObjectInterface $dataObject)
    {
        $fieldName = $this->name;
        if ($dataObject->hasMethod("set$fieldName")) {
            $dataObject->$fieldName = DBField::create_field('WTLink', array(
                        "Type" => $this->fieldType->Value(),
                        "Internal" => $this->internalField->Value(),
                        "External" => $this->externalField->Value(),
                        "Email" => $this->emailField->Value(),
                        "File" => $this->fileField->Value(),
                        "TargetBlank" => $this->targetBlankField->Value()
                ));
        } else {
            if (!empty($dataObject->$fieldName)) {
                $dataObject->$fieldName->setType($this->fieldType->Value());
                $dataObject->$fieldName->setInternal($this->internalField->Value());
                $dataObject->$fieldName->setExternal($this->externalField->Value());
                $dataObject->$fieldName->setEmail($this->emailField->Value());
                $dataObject->$fieldName->setFile($this->fileField->Value());
                $dataObject->$fieldName->setTargetBlank($this->targetBlankField->Value());
            }
        }
    }

    public function setValue($val, $data = null)
    {
        $this->value = $val;

        if (is_array($val)) {
            $this->fieldType->setValue($val['Type']);
            $this->internalField->setValue($val['Internal']);
            $this->externalField->setValue($val['External']);
            $this->emailField->setValue($val['Email']);
            $this->fileField->setValue($val['File']);
            $this->targetBlankField->setValue(isset($val['TargetBlank']) ? $val['TargetBlank'] : false);
        } elseif ($val instanceof WTLink) {
            $this->fieldType->setValue($val->getType());
            $this->internalField->setValue($val->getInternal());
            $this->externalField->setValue($val->getExternal());
            $this->emailField->setValue($val->getEmail());
            $this->fileField->setValue($val->getFile());
            $this->targetBlankField->setValue($val->getTargetBlank());
        }

            // @todo Format numbers according to current locale, incl.
            //  decimal and thousands signs, while respecting the stored
            //  precision in the database without truncating it during display
            //  and subsequent save operations

            return $this;
    }
}


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


    public function __construct($name = null)
    {
        parent::__construct($name);
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
            $manipulation['fields'][$this->name.'Type'] = DBField::create_field(
                'Varchar',
                $this->getType()
            )->nullValue();
        }

        if ($this->getInternal()) {
            $manipulation['fields'][$this->name.'Internal'] = $this->getInternal();
        } else {
            $manipulation['fields'][$this->name.'Internal'] = DBField::create_field(
                'Int',
                $this->getInternal()
            )->nullValue();
        }

        if ($this->getExternal()) {
            $manipulation['fields'][$this->name.'External'] = $this->prepValueForDB($this->getExternal());
        } else {
            $manipulation['fields'][$this->name.'External'] = DBField::create_field(
                'Varchar',
                $this->getExternal()
            )->nullValue();
        }

        if ($this->getEmail()) {
            $manipulation['fields'][$this->name.'Email'] = $this->prepValueForDB($this->getEmail());
        } else {
            $manipulation['fields'][$this->name.'Email'] = DBField::create_field(
                'Varchar',
                $this->getEmail()
            )->nullValue();
        }

        if ($this->getFile()) {
            $manipulation['fields'][$this->name.'File'] = $this->getFile();
        } else {
            $manipulation['fields'][$this->name.'File'] = DBField::create_field(
                'Int',
                $this->getFile()
            )->nullValue();
        }

        if ($this->getTargetBlank()) {
            $manipulation['fields'][$this->name.'TargetBlank'] = $this->getTargetBlank();
        } else {
            $manipulation['fields'][$this->name.'TargetBlank'] = DBField::create_field(
                'Int',
                $this->getTargetBlank()
            )->nullValue();
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
