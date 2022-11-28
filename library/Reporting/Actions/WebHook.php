<?php
// Icinga Reporting | (c) 2018 Icinga GmbH | GPLv2

namespace Icinga\Module\Reporting\Actions;

use Icinga\Application\Config;
use Icinga\Module\Pdfexport\ProvidedHook\Pdfexport;
use Icinga\Module\Reporting\Hook\ActionHook;
use Icinga\Module\Reporting\Report;
use ipl\Html\Form;

class WebHook extends ActionHook
{
    public function getName()
    {
        return 'Send Webhook';
    }

    public function execute(Report $report, array $config)
    {
        $name = sprintf(
            '%s (%s) %s',
            $report->getName(),
            $report->getTimeframe()->getName(),
            date('Y-m-d H:i')
        );

        $mycurl = curl_init();
        curl_setopt($mycurl, CURLOPT_POST, 1);
        curl_setopt($mycurl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($mycurl, CURLOPT_URL, $config['webhookurl']);


        switch ($config['type']) {
            case 'pdf':
                curl_setopt($mycurl, CURLOPT_HTTPHEADER, array('Content-Type: application/pdf'));
                curl_setopt($mycurl, CURLOPT_POSTFIELDS, Pdfexport::first()->htmlToPdf($report->toPdf()));
                curl_exec($mycurl);
                curl_close($mycurl);
                break;
            case 'csv':
                curl_setopt($mycurl, CURLOPT_HTTPHEADER, array('Content-Type: text/csv'));
                curl_setopt($mycurl, CURLOPT_POSTFIELDS,$report->toCsv());
                curl_exec($mycurl);
                curl_close($mycurl);
                break;
            case 'json':
                curl_setopt($mycurl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                curl_setopt($mycurl, CURLOPT_POSTFIELDS,$report->toJson());
                curl_exec($mycurl);
                curl_close($mycurl);
                break;
            default:
                throw new \InvalidArgumentException();
        }


    }

    public function initConfigForm(Form $form, Report $report)
    {
        $types = ['pdf' => 'PDF'];

        if ($report->providesData()) {
            $types['csv'] = 'CSV';
            $types['json'] = 'JSON';
        }

        $form->addElement('select', 'type', [
            'required'  => true,
            'label'     => t('Type'),
            'options'   => $types
        ]);

        $form->addElement('text', 'webhookurl', [
            'label'         => t('Webhook URL'),
            'placeholder'   => "",
            'required'  => true
        ]);

    }
}
