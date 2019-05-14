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
use SilverStripe\ORM\DataObjectInterface;
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

    public function saveInto(DataObjectInterface $dataObject)
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
