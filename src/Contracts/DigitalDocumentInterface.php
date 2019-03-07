<?php

namespace Weble\FatturaElettronica\Contracts;

use DateTime;
use Weble\FatturaElettronica\DigitalDocument;
use Weble\FatturaElettronica\DigitalDocumentInstance;
use Weble\FatturaElettronica\Enums\TransmissionFormat;

interface DigitalDocumentInterface
{
    public function addDigitalDocumentInstance (DigitalDocumentInstance $instance): DigitalDocumentInterface;

    public function getDocumentInstances (): array;

    public function getCustomer (): BillableInterface;

    public function setCustomer (BillableInterface $customer): DigitalDocumentInterface;

    public function getSupplier (): BillableInterface;

    public function setSupplier (BillableInterface $supplier): DigitalDocumentInterface;

    public function getTransmissionFormat (): TransmissionFormat;

    public function setTransmissionFormat ($transmissionFormat): DigitalDocumentInterface;

    public function getSenderVatId ();

    public function getSendingId ();

    public function setCountryCode ($countryCode): DigitalDocumentInterface;

    public function setSenderPhone ($senderPhone): DigitalDocumentInterface;

    public function getSenderPhone ();

    public function setSenderEmail ($senderEmail): DigitalDocumentInterface;

    public function setSendingId ($sendingId): DigitalDocumentInterface;

    public function getCountryCode ();

    public function setSenderVatId ($senderVatId): DigitalDocumentInterface;

    public function getSenderEmail ();

    public function getCustomerPec ();

    public function setCustomerPec ($customerPec): DigitalDocumentInterface;

    public function getCustomerSdiCode ();

    public function setCustomerSdiCode ($sdiCode): DigitalDocumentInterface;
}