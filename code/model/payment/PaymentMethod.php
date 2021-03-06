<?php

/**
 * 'Abstract' class that you will extend to add payment providers
 * These will automatically be added under the "Payments" tab in
 * Settings, inside the CMS
 *
 *
 */
class PaymentMethod extends DataObject implements PermissionProvider
{

    /**
     * Shall this class appear in the list of payment providers. Use this to
     * hide "high level" classes that you intend to extend
     */
    public static $hidden = false;

    /**
     * The controller this is mapped to
     */
    public static $handler;

    /**
     * Title of this payment method (eg: PayPal, WorldPay, etc)
     */
    public $Title;

    /**
     * Route to icon that is associated with this provider
     */
    public $Icon;

    private static $db = array(
        // Payment Gateway config
        "Summary"           => "Text",
        "Default"           => "Boolean",
        "PaymentInfo"       => "HTMLText"
    );

    private static $has_one = array(
        'ParentConfig'  => 'SiteConfig'
    );

    private static $casting = array(
        'Label' => 'Text'
    );

    private static $summary_fields = array(
        'Title',
        'Summary',
        'Default'
    );

    /*
     * Combine this objects summary and it's icon, if it has one.
     *
     * @return String
     */
    public function getLabel()
    {
        return ($this->Icon) ? '<img class="payment-icon" src="'. $this->Icon .'" /> <span>' . $this->Summary . '</span>' : "<span>{$this->Summary}</span>";
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('ParentConfigID');

        // Setup Payment Gateway type
        $payments = ClassInfo::subclassesFor('PaymentMethod');
        // Remove parent class from list
        unset($payments['PaymentMethod']);

        // Check if any payment types have been hidden and unset
        foreach ($payments as $payment_type) {
            if ($payment_type::$hidden) {
                unset($payments[$payment_type]);
            }
        }

        $classname_field = DropdownField::create('ClassName', 'Type of Payment', $payments)
            ->setHasEmptyDefault(true)
            ->setEmptyString('Select Gateway');

        $fields->addFieldToTab('Root.Main', $classname_field);

        if ($this->ID) {
            $fields->addFieldToTab("Root.Main", TextField::create('Summary', 'Summary message to appear on website'));
            $fields->addFieldToTab("Root.Main", CheckboxField::create('Default', 'Default payment method?'));
            $fields->addFieldToTab("Root.Main", HTMLEditorField::create("PaymentInfo", "Message to appear on payment summary page"));

            // Setup response URL field
            $callback_url = Controller::join_links(
                Director::absoluteBaseURL(),
                Payment_Controller::config()->url_segment,
                "callback",
                $this->ID
            );

            // Setup completed URL
            $complete_url = Controller::join_links(
                Director::absoluteBaseURL(),
                Payment_Controller::config()->url_segment,
                "complete"
            );

            // Setup error URL
            $error_url = Controller::join_links(
                Director::absoluteBaseURL(),
                Payment_Controller::config()->url_segment,
                "complete",
                "error"
            );

            $url_field = ToggleCompositeField::create(
                "PaymentURLS",
                "Payment integration URLs",
                FieldList::create(
                    ReadonlyField::create('ResponseURL', 'Response URL')
                        ->setValue($callback_url),

                    ReadonlyField::create('CompletedURL', 'Completed URL')
                        ->setValue($complete_url),

                    ReadonlyField::create('ErrorURL', 'Error URL')
                        ->setValue($error_url)
                )
            );

            $fields->addFieldToTab("Root.Main", $url_field);
        } else {
            $fields->removeByName('Summary');
            $fields->removeByName('Default');
            $fields->removeByName('GatewayMessage');
            $fields->removeByName('PaymentInfo');
        }

        return $fields;
    }

    // Get relevent payment gateway URL to use in HTML form
    public function GatewayURL()
    {
        return $this->URL;
    }
    
    public function providePermissions()
    {
        return array(
            "MANAGE_PAYMENTS" => array(
                'name' => 'Manage payment methods',
                'help' => 'Allow user to create, edit and delete payment methods',
                'category' => 'Checkout',
                'sort' => 50
            ),
        );
    }
    
    public function canView($member = null)
    {
        $extended = $this->extendedCan('canView', $member);
        if ($extended !== null) {
            return $extended;
        }
        
        return true;
    }
    
    public function canCreate($member = null)
    {
        $extended = $this->extendedCan('canCreate', $member);
        if ($extended !== null) {
            return $extended;
        }
        
        if ($member instanceof Member) {
            $memberID = $member->ID;
        } elseif (is_numeric($member)) {
            $memberID = $member;
        } else {
            $memberID = Member::currentUserID();
        }

        if ($memberID && Permission::checkMember($memberID, array("ADMIN", "MANAGE_PAYMENTS"))) {
            return true;
        } elseif ($memberID && $memberID == $this->CustomerID) {
            return true;
        }

        return false;
    }

    public function canEdit($member = null)
    {
        $extended = $this->extendedCan('canEdit', $member);
        if ($extended !== null) {
            return $extended;
        }
        
        if ($member instanceof Member) {
            $memberID = $member->ID;
        } elseif (is_numeric($member)) {
            $memberID = $member;
        } else {
            $memberID = Member::currentUserID();
        }

        if ($memberID && Permission::checkMember($memberID, array("ADMIN", "MANAGE_PAYMENTS"))) {
            return true;
        } elseif ($memberID && $memberID == $this->CustomerID) {
            return true;
        }

        return false;
    }

    public function canDelete($member = null)
    {
        $extended = $this->extendedCan('canDelete', $member);
        if ($extended !== null) {
            return $extended;
        }
        
        if ($member instanceof Member) {
            $memberID = $member->ID;
        } elseif (is_numeric($member)) {
            $memberID = $member;
        } else {
            $memberID = Member::currentUserID();
        }

        if ($memberID && Permission::checkMember($memberID, array("ADMIN", "MANAGE_PAYMENTS"))) {
            return true;
        } elseif ($memberID && $memberID == $this->CustomerID) {
            return true;
        }

        return false;
    }
}
