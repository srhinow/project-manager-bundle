<?php
/**
 * TL_ROOT/system/modules/invoice_and_offer/languages/de/tl_iao_agreements.php
 *
 * Contao extension: invoice_and_offer
 * Deutsch translation file
 *
 * Copyright : &copy; Sven Rhinow <sven@sr-tag.de>
 * License   : LGPL
 * Author    : Sven Rhinow, http://www.sr-tag.de/
 * Translator: Sven Rhinow (scuM666)
 *
 * This file was created automatically be the TYPOlight extension repository translation module.
 * Do not edit this file manually. Contact the author or translator for this module to establish
 * permanent text corrections which are update-safe.
 */


$GLOBALS['TL_LANG']['tl_iao_agreements']['setting_id']	=	array('Konfiguration','');
$GLOBALS['TL_LANG']['tl_iao_agreements']['pid']	=	array('Projekt','Optional können sie hier das entsprechende Projekt auswählen.');
$GLOBALS['TL_LANG']['tl_iao_agreements']['title'] = array('Bezeichnung','Bezeichnung des Elementes');
$GLOBALS['TL_LANG']['tl_iao_agreements']['agreement_pdf_file'] = array('Vertrag','Vertrag als PDF-Datei zuweisen.');
$GLOBALS['TL_LANG']['tl_iao_agreements']['member'] = array('Kunde','Adresse aus gespeicherten Kunden in nachstehendes Feld befüllen');
$GLOBALS['TL_LANG']['tl_iao_agreements']['address_text'] = array('Mahnungs-Adresse','Adresse die in der Mahnungs-PDF-Datei geschrieben wird.');
$GLOBALS['TL_LANG']['tl_iao_agreements']['published'] = array('veröffentlicht','');
$GLOBALS['TL_LANG']['tl_iao_agreements']['status'] = array('Status dieses Vertrages','');
$GLOBALS['TL_LANG']['tl_iao_agreements']['periode'] = array('Periode','Geben Sie die Periode in Form von strtotime ein z.B. +1 year = 1 Jahr weiter, +2 months = weitere 2 Monate');
$GLOBALS['TL_LANG']['tl_iao_agreements']['agreement_date'] = array('Vertrag seit','');
$GLOBALS['TL_LANG']['tl_iao_agreements']['beginn_date'] = array('Zyklusbeginn','');
$GLOBALS['TL_LANG']['tl_iao_agreements']['end_date'] = array('Zyklusende','');
$GLOBALS['TL_LANG']['tl_iao_agreements']['terminated_date'] = array('gekündigt zum','');
$GLOBALS['TL_LANG']['tl_iao_agreements']['new_generate'] = array('den neuen Zyklus setzen','');
$GLOBALS['TL_LANG']['tl_iao_agreements']['sendEmail'] = array('Email-Erinnerung einrichten','');
$GLOBALS['TL_LANG']['tl_iao_agreements']['remind_before'] = array('erinnern vor Ablauf des Vertrags-Zyklus','Die Angaben müssen im strtotime-Format (z.B. -3 weeks) gesetzt werden.');
$GLOBALS['TL_LANG']['tl_iao_agreements']['email_from'] = array('Email-Sender','Die Absendeadresse sollte hier zur Domain gehören, sonst kann es sein das der Server diese Email nicht sendet.');
$GLOBALS['TL_LANG']['tl_iao_agreements']['email_to'] = array('Email-Empfänger','Die Emailadresse, zur der die Erinnerung das eine Vertragsperiode endet, gesendet werden soll.');
$GLOBALS['TL_LANG']['tl_iao_agreements']['email_subject'] = array('Email-Betreff','');
$GLOBALS['TL_LANG']['tl_iao_agreements']['email_text'] = array('Email-Text','');
$GLOBALS['TL_LANG']['tl_iao_agreements']['before_template'] = array('Text vor den Posten','Eine Auswahl an Rechnungsvorlagen');
$GLOBALS['TL_LANG']['tl_iao_agreements']['after_template'] = array('Text nach den Posten','Eine Auswahl an Rechnungsvorlagen');
$GLOBALS['TL_LANG']['tl_iao_agreements']['posten_template'] = array('Posten-Template','Eine Auswahl an Rechnungsposten');
$GLOBALS['TL_LANG']['tl_iao_agreements']['notice'] = array('Notiz','');
$GLOBALS['TL_LANG']['tl_iao_agreements']['price'] = array('Preis','Geben Sie hier den Preis an.');
$GLOBALS['TL_LANG']['tl_iao_agreements']['vat_incl'] = array('Preis-Angabe mit oder ohne MwSt.','(brutto / netto)');
$GLOBALS['TL_LANG']['tl_iao_agreements']['count'] = array('Anzahl','Die Anzahl wird mit dem Preis multipliziert.');
$GLOBALS['TL_LANG']['tl_iao_agreements']['amountStr'] = array('Art der Anzahl','');
$GLOBALS['TL_LANG']['tl_iao_agreements']['vat'] = array('MwSt.','Art der MwSt. zu diesem Posten.');

$GLOBALS['TL_LANG']['tl_iao_agreements']['execute_date'] = array('Ausgeführt am','Dieses Angabe wird vom Finanzamt vorgeschrieben um die Vorsteuer zu ziehen.');
$GLOBALS['TL_LANG']['tl_iao_agreements']['expiry_date'] = array('Begleichen bis','Das Datum nachdem die Mahnungsstufen anfangen.');
$GLOBALS['TL_LANG']['tl_iao_agreements']['invoice_pdf_file'] = array('Mahnungsdatei','Wenn hier eine Datei angegeben wurde wird diese statt einer generierten ausgegeben. Unter normalen Umständen sollte dieses Feld leer bleiben. Es ist hauptsächlich für Importe gedacht.');
$GLOBALS['TL_LANG']['tl_iao_agreements']['paid_on_date'] = array('Bezahlt am','Das Datum an dem die Zahlung auf dem Konto eingegangen ist.');
$GLOBALS['TL_LANG']['tl_iao_agreements']['invoice'] = array('Für den Servicevertrag die aktuelle Rechnung generieren.','Servicevertrag ID %s als Rechnung anlegen');
$GLOBALS['TL_LANG']['tl_iao_agreements']['toggle'] = 'Vertrag als aktiv/ nicht aktiv markieren';

/**
* Buttons
*/
$GLOBALS['TL_LANG']['tl_iao_agreements']['new'] = array('Neuer Vertrag','Einen neuen Vertrag anlegen');
$GLOBALS['TL_LANG']['tl_iao_agreements']['edit'] = array('Vertrag bearbeiten','Vertrag ID %s bearbeiten');
$GLOBALS['TL_LANG']['tl_iao_agreements']['copy'] = array('Vertrag duplizieren','Vertrag ID %s duplizieren');
$GLOBALS['TL_LANG']['tl_iao_agreements']['delete'] = array('Vertrag löschen','Vertrag ID %s löschen');
$GLOBALS['TL_LANG']['tl_iao_agreements']['deleteConfirm'] = 'Soll die Vertrag ID %s wirklich gelöscht werden?!';
$GLOBALS['TL_LANG']['tl_iao_agreements']['show'] = array('Details anzeigen','Details der Vertrag ID %s anzeigen');

/**
 * Legend
 */
$GLOBALS['TL_LANG']['tl_iao_agreements']['invoice_generate_legend']	=	'Einstellungen für die Genrierung der Rechnungen';
$GLOBALS['TL_LANG']['tl_iao_agreements']['agreement_legend']	=	'Vertrag-Einstellungen';
$GLOBALS['TL_LANG']['tl_iao_agreements']['settings_legend']	=	'Konfiguration-Zuweisung';
$GLOBALS['TL_LANG']['tl_iao_agreements']['title_legend'] = 'Titel Einstellung';
$GLOBALS['TL_LANG']['tl_iao_agreements']['address_legend'] = 'Adress-Angaben';
$GLOBALS['TL_LANG']['tl_iao_agreements']['status_legend'] = 'Status-Einstellungen';
$GLOBALS['TL_LANG']['tl_iao_agreements']['email_legend'] = 'Email-Einstellungen';
$GLOBALS['TL_LANG']['tl_iao_agreements']['other_legend'] = 'weitere Einstellungen';
$GLOBALS['TL_LANG']['tl_iao_agreements']['notice_legend'] = 'Notiz anlegen';

/**
 * Select-fiels options
 */
$GLOBALS['TL_LANG']['tl_iao_agreements']['status_options'] = array('1'=>'aktiv','2'=>'gekündigt');
$GLOBALS['TL_LANG']['tl_iao_agreements']['vat_incl_percents'] = array(1 => 'netto', 2 => 'brutto');

/**
* Frontend-Templates
*/
$GLOBALS['TL_LANG']['tl_iao_agreements']['fe_table_head']['title'] = 'Titel:';
$GLOBALS['TL_LANG']['tl_iao_agreements']['fe_table_head']['beginn_date'] = 'Zyklusbeginn:';
$GLOBALS['TL_LANG']['tl_iao_agreements']['fe_table_head']['end_date'] = 'Zyklusende:';
$GLOBALS['TL_LANG']['tl_iao_agreements']['fe_table_head']['price'] = 'Betrag:';
$GLOBALS['TL_LANG']['tl_iao_agreements']['fe_table_head']['file'] = 'Vertrag (pdf):';


// Meldungen
$GLOBALS['TL_LANG']['tl_iao_agreements']['no_entries_msg'] = 'Es sind keine Einträge für diesen Bereich vorhanden.';

