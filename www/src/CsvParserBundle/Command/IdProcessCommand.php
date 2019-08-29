<?php

namespace CsvParserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IdProcessCommand extends ContainerAwareCommand
{

    /**
     * Configure or register command for CMD
     */
    protected function configure()
    {
        $this
            ->setName('id:process')
            ->setDescription('...')
            ->addArgument('argument', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option', null, InputOption::VALUE_NONE, 'Option description');
    }

    /**
     * Execute the function when run command id:process
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $argument = $input->getArgument('argument');
        $CsvUploadBaseDir = realpath($this->getContainer()->getParameter('csv_upload_dir'));
        $filename = $argument;

        if (!$filename) {
            $output->writeln('Please enter valid CSV file name and file should be located to this directory "' . $CsvUploadBaseDir . '"');
            return;
        }

        $filename_ = $CsvUploadBaseDir . '/' . $filename;
        if (!is_file($filename_) || !file_exists($filename_)) {
            $output->writeln("Enter valid file name");
        }


        $csvToArr = array_map('str_getcsv', file($filename_));
        foreach ($csvToArr as $i => $row) {
            $columnCount = count($row);
            if ($columnCount < 6) {
                unset($csvToArr[$i]);
                $output->writeln("Invalid line or row in csv");
                continue;
            }
            $output->writeln($this->validateIdentification($row, $csvToArr));
        }
        $output->writeln('');

    }


    /**
     * Validate identification data
     *
     * @param $data
     * @param $originalDataRows
     * @return string
     */
    public function validateIdentification($data, $originalDataRows)
    {
        $responseCode = '';
        list($requestDate, $countryCode, $type, $number, $issueDate, $personalIdentificationNumber) = $data;
        $allowedPassportType = ['passport', 'identity_card', 'residence_permit'];
        $documentExpireInyear = 5;
        $issueValidDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        switch (strtolower($countryCode)) {
            case 'de':
                if ($type == 'identity_card') {
                    if (strtotime($issueDate) >= strtotime('2010-01-01')) {
                        $documentExpireInyear = 10;
                    }
                }

                //document_type_is_invalid
                if (!in_array($type, $allowedPassportType)) {
                    $responseCode = 'document_type_is_invalid';
                }

                //document_is_expired
                if (empty($responseCode)) {
                    $issueTime = strtotime($issueDate, time());
                    $expireTime = strtotime("+{$documentExpireInyear} years", strtotime($issueDate));
                    if (($expireTime - $issueTime) > 0) {
                        $responseCode = 'document_is_expired';
                    }
                }

                //document_number_length_invalid
                if (empty($responseCode)) {
                    if (strlen((string)$number) != 8) {
                        $responseCode = 'document_number_length_invalid';
                    }
                }

                //document_issue_date_invalid
                if (empty($responseCode)) {
                    $issueDay = strtolower(date('l', strtotime($issueDate)));
                    if (!in_array($issueDay, $issueValidDays)) {
                        $responseCode = 'document_issue_date_invalid';
                    }
                }

                //request_limit_exceeded
                if (empty($responseCode)) {
                    $requestDateTime = strtotime($requestDate);
                    $issueDay = strtolower(date('l', $requestDateTime));
                    if ($issueDay == 'monday') {
                        $weekStart = date('Y-m-d 00:00:00', strtotime($requestDate));
                    } else {
                        $weekStart = date('Y-m-d 00:00:00', strtotime("last Monday", strtotime($requestDate)));
                    }
                    $weekStart = strtotime($weekStart);
                    $weekEnd = strtotime("+5 days", strtotime($weekStart));

                    $identificationRequestCounter = 0;
                    foreach ($originalDataRows as $originalDataRow) {
                        $tmoIdNUmbe = $originalDataRow[3];
                        if ($tmoIdNUmbe == $number) {
                            if ($requestDateTime >= $weekStart || $requestDateTime <= $weekEnd) {
                                $identificationRequestCounter++;
                            }
                        }
                    }

                    if ($identificationRequestCounter > 2) {
                        $responseCode = 'request_limit_exceeded';
                    }

                }

                break;
            case 'es':

                //document_type_is_invalid
                if (!in_array($type, $allowedPassportType)) {
                    $responseCode = 'document_type_is_invalid';
                }

                //document_is_expired
                if (empty($responseCode)) {

                    if ($type == 'passport') {
                        if (strtotime($issueDate) >= strtotime('2013-02-14')) {
                            $documentExpireInyear = 15;
                        }
                    }

                    $issueTime = strtotime($issueDate, time());
                    $expireTime = strtotime("+{$documentExpireInyear} years", strtotime($issueDate));
                    if (($expireTime - $issueTime) > 0) {
                        $responseCode = 'document_is_expired';
                    }
                }

                //document_number_length_invalid
                if (empty($responseCode)) {
                    if (strlen((string)$number) != 8) {
                        $responseCode = 'document_number_length_invalid';
                    } else {
                        //document_number_invalid
                        if ($number >= 50001111 || $number <= 50009999) {
                            $responseCode = 'document_number_invalid';
                        }
                    }
                }


                //document_issue_date_invalid
                if (empty($responseCode)) {
                    $issueDay = strtolower(date('l', strtotime($issueDate)));
                    if (!in_array($issueDay, $issueValidDays)) {
                        $responseCode = 'document_issue_date_invalid';
                    }
                }

                //request_limit_exceeded
                if (empty($responseCode)) {
                    $requestDateTime = strtotime($requestDate);
                    $issueDay = strtolower(date('l', $requestDateTime));
                    if ($issueDay == 'monday') {
                        $weekStart = date('Y-m-d 00:00:00', strtotime($requestDate));
                    } else {
                        $weekStart = date('Y-m-d 00:00:00', strtotime("last Monday", strtotime($requestDate)));
                    }
                    $weekStart = strtotime($weekStart);
                    $weekEnd = strtotime("+5 days", strtotime($weekStart));

                    $identificationRequestCounter = 0;
                    foreach ($originalDataRows as $originalDataRow) {
                        $tmoIdNUmbe = $originalDataRow[3];
                        if ($tmoIdNUmbe == $number) {
                            if ($requestDateTime >= $weekStart || $requestDateTime <= $weekEnd) {
                                $identificationRequestCounter++;
                            }
                        }
                    }

                    if ($identificationRequestCounter > 2) {
                        $responseCode = 'request_limit_exceeded';
                    }
                }
                break;
            case 'fr':
                $allowedPassportType[] = 'drivers_license';
                //document_type_is_invalid
                if (!in_array($type, $allowedPassportType)) {
                    $responseCode = 'document_type_is_invalid';
                }

                //document_is_expired
                if (empty($responseCode)) {
                    if ($type != 'drivers_license') {
                        $issueTime = strtotime($issueDate, time());
                        $expireTime = strtotime("+{$documentExpireInyear} years", strtotime($issueDate));
                        if (($expireTime - $issueTime) > 0) {
                            $responseCode = 'document_is_expired';
                        }

                    }
                }

                //document_number_length_invalid
                if (empty($responseCode)) {
                    if (strlen((string)$number) != 8) {
                        $responseCode = 'document_number_length_invalid';
                    }
                }


                //document_issue_date_invalid
                if (empty($responseCode)) {
                    $issueDay = strtolower(date('l', strtotime($issueDate)));
                    if (!in_array($issueDay, $issueValidDays)) {
                        $responseCode = 'document_issue_date_invalid';
                    }
                }

                //request_limit_exceeded
                if (empty($responseCode)) {
                    $requestDateTime = strtotime($requestDate);
                    $issueDay = strtolower(date('l', $requestDateTime));
                    if ($issueDay == 'monday') {
                        $weekStart = date('Y-m-d 00:00:00', strtotime($requestDate));
                    } else {
                        $weekStart = date('Y-m-d 00:00:00', strtotime("last Monday", strtotime($requestDate)));
                    }
                    $weekStart = strtotime($weekStart);
                    $weekEnd = strtotime("+5 days", strtotime($weekStart));

                    $identificationRequestCounter = 0;
                    foreach ($originalDataRows as $originalDataRow) {
                        $tmoIdNUmbe = $originalDataRow[3];
                        if ($tmoIdNUmbe == $number) {
                            if ($requestDateTime >= $weekStart || $requestDateTime <= $weekEnd) {
                                $identificationRequestCounter++;
                            }
                        }
                    }

                    if ($identificationRequestCounter > 2) {
                        $responseCode = 'request_limit_exceeded';
                    }
                }
                break;
            case 'pl':
                //document_type_is_invalid
                if (!in_array($type, $allowedPassportType)) {
                    $responseCode = 'document_type_is_invalid';
                } else {
                    if ($type == 'residence_permit') {
                        if (strtotime($issueDate) >= strtotime('2018-09-01')) {
                            //valid
                        } else {
                            $responseCode = 'document_type_is_invalid';
                        }
                    }
                }

                //document_is_expired
                if (empty($responseCode)) {
                    $issueTime = strtotime($issueDate, time());
                    $expireTime = strtotime("+{$documentExpireInyear} years", strtotime($issueDate));
                    if (($expireTime - $issueTime) > 0) {
                        $responseCode = 'document_is_expired';
                    }
                }

                //document_number_length_invalid
                if (empty($responseCode)) {
                    if (strtotime($issueDate) >= strtotime('2018-09-01')) {
                        if (strlen((string)$number) != 10) {
                            $responseCode = 'document_number_length_invalid';
                        }
                    } else {
                        if (strlen((string)$number) != 8) {
                            $responseCode = 'document_number_length_invalid';
                        }
                    }
                }


                //document_issue_date_invalid
                if (empty($responseCode)) {
                    $issueDay = strtolower(date('l', strtotime($issueDate)));
                    if (!in_array($issueDay, $issueValidDays)) {
                        $responseCode = 'document_issue_date_invalid';
                    }
                }

                //request_limit_exceeded
                if (empty($responseCode)) {
                    $requestDateTime = strtotime($requestDate);
                    $issueDay = strtolower(date('l', $requestDateTime));
                    if ($issueDay == 'monday') {
                        $weekStart = date('Y-m-d 00:00:00', strtotime($requestDate));
                    } else {
                        $weekStart = date('Y-m-d 00:00:00', strtotime("last Monday", strtotime($requestDate)));
                    }
                    $weekStart = strtotime($weekStart);
                    $weekEnd = strtotime("+5 days", strtotime($weekStart));

                    $identificationRequestCounter = 0;
                    foreach ($originalDataRows as $originalDataRow) {
                        $tmoIdNUmbe = $originalDataRow[3];
                        if ($tmoIdNUmbe == $number) {
                            if ($requestDateTime >= $weekStart || $requestDateTime <= $weekEnd) {
                                $identificationRequestCounter++;
                            }
                        }
                    }

                    if ($identificationRequestCounter > 2) {
                        $responseCode = 'request_limit_exceeded';
                    }
                }

                break;
            case 'it':

                //document_type_is_invalid
                if (!in_array($type, $allowedPassportType)) {
                    $responseCode = 'document_type_is_invalid';
                }

                //document_is_expired
                if (empty($responseCode)) {
                    $issueTime = strtotime($issueDate, time());
                    $expireTime = strtotime("+{$documentExpireInyear} years", strtotime($issueDate));
                    if (($expireTime - $issueTime) > 0) {
                        $responseCode = 'document_is_expired';
                    }
                }

                //document_number_length_invalid
                if (empty($responseCode)) {
                    if (strlen((string)$number) != 8) {
                        $responseCode = 'document_number_length_invalid';
                    }
                }

                //document_issue_date_invalid
                if (empty($responseCode)) {
                    $requestDateTime = strtotime($requestDate);
                    $requestExceptionStart = strtotime('2019-01-01');
                    $requestExceptionEnd = strtotime('2019-01-31');
                    if ($requestExceptionStart >= $requestDateTime || $requestExceptionEnd <= $requestDateTime) {
                        $issueValidDays[] = 'saturday';
                    }

                    $issueDay = strtolower(date('l', strtotime($issueDate)));
                    if (!in_array($issueDay, $issueValidDays)) {
                        $responseCode = 'document_issue_date_invalid';
                    }
                }

                //request_limit_exceeded
                if (empty($responseCode)) {
                    $requestDateTime = strtotime($requestDate);
                    $issueDay = strtolower(date('l', $requestDateTime));
                    if ($issueDay == 'monday') {
                        $weekStart = date('Y-m-d 00:00:00', strtotime($requestDate));
                    } else {
                        $weekStart = date('Y-m-d 00:00:00', strtotime("last Monday", strtotime($requestDate)));
                    }
                    $weekStart = strtotime($weekStart);
                    $weekEnd = strtotime("+6 days", strtotime($weekStart));

                    $identificationRequestCounter = 0;
                    foreach ($originalDataRows as $originalDataRow) {
                        $tmoIdNUmbe = $originalDataRow[3];
                        if ($tmoIdNUmbe == $number) {
                            if ($requestDateTime >= $weekStart || $requestDateTime <= $weekEnd) {
                                $identificationRequestCounter++;
                            }
                        }
                    }

                    if ($identificationRequestCounter > 2) {
                        $responseCode = 'request_limit_exceeded';
                    }

                }

                break;
            case 'uk':
                //document_type_is_invalid
                $requestDateTime = strtotime($requestDate);
                if ($requestDateTime >= strtotime('2019-01-01')) {
                    if ($type != 'passport') {
                        $responseCode = 'document_type_is_invalid';
                    }
                } else {
                    if (!in_array($type, $allowedPassportType)) {
                        $responseCode = 'document_type_is_invalid';
                    }
                }

                //document_is_expired
                if (empty($responseCode)) {
                    $issueTime = strtotime($issueDate, time());
                    $expireTime = strtotime("+{$documentExpireInyear} years", strtotime($issueDate));
                    if (($expireTime - $issueTime) > 0) {
                        $responseCode = 'document_is_expired';
                    }
                }

                //document_number_length_invalid
                if (empty($responseCode)) {
                    if (strlen((string)$number) != 8) {
                        $responseCode = 'document_number_length_invalid';
                    }
                }

                //document_issue_date_invalid
                if (empty($responseCode)) {
                    $issueDay = strtolower(date('l', strtotime($issueDate)));
                    if (!in_array($issueDay, $issueValidDays)) {
                        $responseCode = 'document_issue_date_invalid';
                    }
                }

                //request_limit_exceeded
                if (empty($responseCode)) {
                    $requestDateTime = strtotime($requestDate);
                    $issueDay = strtolower(date('l', $requestDateTime));
                    if ($issueDay == 'monday') {
                        $weekStart = date('Y-m-d 00:00:00', strtotime($requestDate));
                    } else {
                        $weekStart = date('Y-m-d 00:00:00', strtotime("last Monday", strtotime($requestDate)));
                    }
                    $weekStart = strtotime($weekStart);
                    $weekEnd = strtotime("+5 days", strtotime($weekStart));

                    $identificationRequestCounter = 0;
                    foreach ($originalDataRows as $originalDataRow) {
                        $tmoIdNUmbe = $originalDataRow[3];
                        if ($tmoIdNUmbe == $number) {
                            if ($requestDateTime >= $weekStart || $requestDateTime <= $weekEnd) {
                                $identificationRequestCounter++;
                            }
                        }
                    }

                    if ($identificationRequestCounter > 2) {
                        $responseCode = 'request_limit_exceeded';
                    }

                }
                break;

        }

        if (empty($responseCode)) {
            $responseCode = 'valid';
        }
        return $responseCode;

    }

}
