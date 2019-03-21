<?php

namespace Weble\FatturaElettronica\Writer\Body;


use Weble\FatturaElettronica\Contracts\OtherDataInterface;
use Weble\FatturaElettronica\Exceptions\InvalidDocument;
use Weble\FatturaElettronica\Utilities\SimpleXmlExtended;

class ProductsWriter extends AbstractBodyWriter
{
    protected function performWrite()
    {
        $datiBeniServizi = $this->xml->addChild('DatiBeniServizi');

        /** @var \Weble\FatturaElettronica\Contracts\LineInterface $line */
        foreach ($this->body->getLines() as $line) {
            $this->addLine($datiBeniServizi, $line);
        }

        /** @var \Weble\FatturaElettronica\Contracts\TotalInterface $summary */
        foreach ($this->body->getTotals() as $summary) {
            $this->addTotal($datiBeniServizi, $summary);
        }
    }

    /**
     * @param \SimpleXMLElement $dettaglioLinee
     * @param $otherData
     */
    protected function addOtherData(\SimpleXMLElement $dettaglioLinee, OtherDataInterface $otherData): void
    {
        $altriDatiGestionali = $dettaglioLinee->addChild('AltriDatiGestionali');
        $altriDatiGestionali->addChild('TipoDato', $otherData->getType());

        $value = $otherData->getText();
        if ($value !== null) {
            $altriDatiGestionali->addChild('RiferimentoTesto', $value);
        }

        $value = $otherData->getDate();
        if ($value !== null) {
            $altriDatiGestionali->addChild('RiferimentoData', $value->format('Y-m-d'));
        }

        $importoEnasarco = $otherData->getNumber();
        if ($importoEnasarco !== null) {
            if ($importoEnasarco <= 0) {
                $importoEnasarco = 0.00;
            }

            $altriDatiGestionali->addChild('RiferimentoNumero', $importoEnasarco);
        }
    }

    /**
     * @param \SimpleXMLElement $dettaglioLinee
     * @param \Weble\FatturaElettronica\Contracts\DiscountInterface $discount
     */
    protected function addDiscount(
        \SimpleXMLElement $dettaglioLinee,
        \Weble\FatturaElettronica\Contracts\DiscountInterface $discount
    ): void {
        $scontoMaggiorazione = $dettaglioLinee->addChild('ScontoMaggiorazione');
        $scontoMaggiorazione->addChild('Tipo', (string)$discount->getType());

        if ($discount->getPercentage() !== null) {
            $scontoMaggiorazione->addChild('Percentuale', SimpleXmlExtended::sanitizeFloat($discount->getPercentage()));
        }

        if ($discount->getAmount() !== null) {
            $scontoMaggiorazione->addChild('Importo', SimpleXmlExtended::sanitizeFloat($discount->getAmount()));
        }
    }

    /**
     * @param \SimpleXMLElement $dettaglioLinee
     * @param \Weble\FatturaElettronica\Contracts\ProductInterface $product
     */
    protected function addProduct(
        \SimpleXMLElement $dettaglioLinee,
        \Weble\FatturaElettronica\Contracts\ProductInterface $product
    ): void {
        $codiceArticolo = $dettaglioLinee->addChild('CodiceArticolo');
        $codiceArticolo->addChild('CodiceTipo', $product->getCodeType());
        $codiceArticolo->addChild('CodiceValore', SimpleXmlExtended::sanitizeText($product->getCode()));
    }

    /**
     * @param \SimpleXMLElement $datiBeniServizi
     * @param \Weble\FatturaElettronica\Contracts\LineInterface $line
     */
    protected function addLine(
        \SimpleXMLElement $datiBeniServizi,
        \Weble\FatturaElettronica\Contracts\LineInterface $line
    ): void {
        $dettaglioLinee = $datiBeniServizi->addChild('DettaglioLinee');

        $dettaglioLinee->addChild('NumeroLinea', $line->getNumber());

        if ($line->getTipoCessazionePrestazione() !== null) {
            $dettaglioLinee->addChild('TipoCessionePrestazione', (string)$line->getTipoCessazionePrestazione());
        }

        $products = $line->getProducts();
        if (count($products) > 0) {
            /** @var \Weble\FatturaElettronica\Contracts\ProductInterface $product */
            foreach ($products as $product) {
                $this->addProduct($dettaglioLinee, $product);
            }
        }

        $dettaglioLinee->addChild('Descrizione', SimpleXmlExtended::sanitizeText($line->getDescription()));

        $quantita = $line->getQuantity();
        if ($quantita === null) {
            $quantita = 1;
        }

        $dettaglioLinee->addChild('Quantita',SimpleXmlExtended::sanitizeFloat($quantita));

        if ($line->getUnit() !== null) {
            $dettaglioLinee->addChild('UnitaMisura', $line->getUnit());
        }

        if ($line->getStartDate() !== null) {
            $dettaglioLinee->addChild('DataInizioPeriodo', $line->getStartDate()->format('Y-m-d'));
        }

        if ($line->getEndDate() !== null) {
            $dettaglioLinee->addChild('DataFinePeriodo', $line->getEndDate()->format('Y-m-d'));
        }

        $precision = 2;
        $maxPrecision = 8;
        $difference = abs($line->getUnitPrice() - round($line->getUnitPrice(), $precision));
        while($precision <= $maxPrecision && $difference > 0) {
            $precision++;
            $difference = abs($line->getUnitPrice() - round($line->getUnitPrice(), $precision));
        }

        $dettaglioLinee->addChild('PrezzoUnitario', number_format($line->getUnitPrice(), $precision, '.', ''));

        $discounts = $line->getDiscounts();
        if (count($discounts) > 0) {
            /** @var \Weble\FatturaElettronica\Contracts\DiscountInterface $discount */
            foreach ($discounts as $discount) {
                $this->addDiscount($dettaglioLinee, $discount);
            }
        }

        $dettaglioLinee->addChild('PrezzoTotale',SimpleXmlExtended::sanitizeFloat($line->getTotal()));

        $dettaglioLinee->addChild('AliquotaIVA',SimpleXmlExtended::sanitizeFloat($line->getTaxPercentage()));

        if ($line->isDeduction()) {
            $dettaglioLinee->addChild('Ritenuta', 'SI');
        }

        $natura = $line->getVatNature();
        if ($natura !== null) {
            $dettaglioLinee->addChild('Natura', (string)$natura);
        }

        if ($line->getAdministrativeContact()) {
            $dettaglioLinee->addChild('RiferimentoAmministrazione', $line->getAdministrativeContact());
        }

        $otherData = $line->getOtherData();
        if ($otherData !== null) {
            foreach ($otherData as $o) {
                $this->addOtherData($dettaglioLinee, $o);
            }

        }
    }

    /**
     * @param \SimpleXMLElement $datiBeniServizi
     * @param \Weble\FatturaElettronica\Contracts\TotalInterface $summary
     */
    protected function addTotal(
        \SimpleXMLElement $datiBeniServizi,
        \Weble\FatturaElettronica\Contracts\TotalInterface $summary
    ): void {
        $datiRiepilogo = $datiBeniServizi->addChild('DatiRiepilogo');

        $datiRiepilogo->addChild('AliquotaIVA', SimpleXmlExtended::sanitizeFloat($summary->getTaxPercentage()));

        $taxType = $summary->getVatNature();
        if ($taxType !== null) {
            $datiRiepilogo->addChild('Natura', (string)$taxType);
        }

        if ($summary->getOtherExpenses() !== null) {
            $datiRiepilogo->addChild('SpeseAccessorie', SimpleXmlExtended::sanitizeFloat($summary->getOtherExpenses()));
        }

        if ($summary->getRounding() !== null) {
            $datiRiepilogo->addChild('Arrotondamento', SimpleXmlExtended::sanitizeFloat($summary->getRounding()));
        }

        $imponibileImporto = $summary->getTotal();
        if (empty($imponibileImporto)) {
            $imponibileImporto = 0.00;
        }

        $imposta = $summary->getTaxAmount();
        if (empty($imposta)) {
            $imposta = 0.00;
        }

        $datiRiepilogo->addChild('ImponibileImporto', SimpleXmlExtended::sanitizeFloat($imponibileImporto));
        $datiRiepilogo->addChild('Imposta', SimpleXmlExtended::sanitizeFloat($imposta));

        if ($summary->getTaxType() !== null) {
            $datiRiepilogo->addChild('EsigibilitaIVA', (string)$summary->getTaxType());
        }

        if ($summary->getReference() !== null) {
            $datiRiepilogo->addChild('RiferimentoNormativo', SimpleXmlExtended::sanitizeText($summary->getReference()));
        }
    }
}
