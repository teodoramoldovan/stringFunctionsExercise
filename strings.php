<?php
const PARTS = ['firstName', 'lastName', 'cnp', 'activities', 'contributions'];
const ACTIVITIES_PARTS = ['activityCode', 'name', 'hours', 'hourlyRate', 'sum'];
const ACTIVITIES_HEADER_ARRAY = ['Cod activitate', 'Nume activitate', 'Ore', 'Rata orara', 'Suma primita'];
const PADDING_ACTIVITY_COLUMN_ONE = 16;
const PADDING_ACTIVITY_COLUMN_TWO = 19;

/**
 * @param string $employeeRegistration
 * @return array
 */
function splitString(string $employeeRegistration): array
{
    $registationParts = explode('|', $employeeRegistration);
    $registrationData = array_combine(PARTS, $registationParts);
    return $registrationData;

}

/**
 * @param string $firstName
 * @param string $lastName
 * @return string
 */
function getFormattedName(string $firstName, string $lastName): string
{
    $firstNames = explode('+', $firstName);
    $processedFirstName = implode(' ', $firstNames);
    $processedFirstName = ucwords($processedFirstName);
    $processedLastName = mb_strtoupper($lastName);
    return $processedLastName . " " . $processedFirstName;
}

/**
 * @param string $string
 * @param int $paddingSize
 * @param $paddingType
 * @return string
 */
function insertSpacePadding(string $string, int $paddingSize, $paddingType): string
{
    return str_pad($string, $paddingSize, ' ', $paddingType);
}

/**
 * @param string $name
 * @param string $cnp
 */
function displayPersonalInformation(string $name, string $cnp)
{
    $reformattedName = insertSpacePadding("Nume", 16, STR_PAD_RIGHT);
    $reformattedCNP = insertSpacePadding("CNP", 16, STR_PAD_RIGHT);
    echo $reformattedName . '|' . $name . PHP_EOL . $reformattedCNP . '|' . $cnp . PHP_EOL . PHP_EOL;
}

/**
 * @param string $time
 * @return float
 */
function convertHoursAndMinutesToFloat(string $time): float
{
    [$unprocessedHours, $unprocessedMinutes] = explode('h', $time);
    $hours = (int)$unprocessedHours;
    $minutes = (int)trim($unprocessedMinutes, 'm');
    return ($hours * 60 + $minutes) / 60;

}

/**
 * @param string $hoursAndSum
 * @return array
 */
function processHoursRateSum(string $hoursAndSum): array
{
    $splitArray = explode('*', $hoursAndSum);
    $hourlyRate = trim($splitArray[1], '/h');
    if (strpos($splitArray[0], 'm') == false) {
        $splitArray[0] = (float)trim($splitArray[0], 'h');
    } else $splitArray[0] = convertHoursAndMinutesToFloat($splitArray[0]);
    $splitArray[1] = (float)$hourlyRate;
    $splitArray[2] = $splitArray[0] * $splitArray[1];//time in hours*hourly rate
    return $splitArray;
}

/**
 * @param array $a
 * @param array $b
 * @return int
 */
function cmp(array $a, array $b): int
{
    return strnatcmp($a[0], $b[0]);
}

/**
 * @param string $unprocessedActivitiesString
 * @return array
 */
function getSortedActivities(string $unprocessedActivitiesString): array
{
    $unprocessedActivities = explode(',', $unprocessedActivitiesString);
    foreach ($unprocessedActivities as $unprocessedActivity) {
        $unprocessedActivity = trim($unprocessedActivity, '[]');
        $activities[] = explode(';', $unprocessedActivity);
    }
    foreach ($activities as $activity) {
        $hoursRateSum[] = processHoursRateSum($activity[2]);

    }
    for ($i = 0; $i < sizeof($activities); $i++) {
        unset($activities[$i][2]);

        $processedActivities[] = array_merge($activities[$i], $hoursRateSum[$i]);
    }
    usort($processedActivities, "cmp");
    return $processedActivities;
}

/**
 * Prints activities table.
 *
 * @param array $tableRows
 */
function printActivitiesTable(array $tableRows): void
{
    foreach ($tableRows as $key => $row) {
        $column1 = insertSpacePadding($row[0], PADDING_ACTIVITY_COLUMN_ONE, STR_PAD_RIGHT);
        $column2 = insertSpacePadding($row[1], PADDING_ACTIVITY_COLUMN_TWO, STR_PAD_RIGHT);
        $paddingAlignment = $key == 0 ? STR_PAD_RIGHT : STR_PAD_LEFT;
        $column3 = insertSpacePadding($row[2], 6, $paddingAlignment);
        $column4 = insertSpacePadding($row[3], 10, $paddingAlignment);
        $column5 = insertSpacePadding($row[4], 12, $paddingAlignment);
        echo $column1 . '|' . $column2 . '|' . $column3 . '|' . $column4 . '|' . $column5 . PHP_EOL;
    }

    echo str_pad('', 67, '-', STR_PAD_BOTH) . PHP_EOL;
}

/**
 * @param array $activities data structure
 */
function displayActivities(array $activities)
{
    // formatting activity for display
    foreach ($activities as $activity) {
        $activity[3] = money_format('%.2i', $activity[3]);
        $activity[4] = money_format('%.2i', $activity[4]);
    }
    // add the header row
    array_unshift($activities, ACTIVITIES_HEADER_ARRAY);

    // print the table
    printActivitiesTable($activities);

}

/**
 * @param array $activities
 * @return float
 */
function computeGrossTotal(array $activities): float
{
    $grossTotal = 0;
    foreach ($activities as $activity) {
        $grossTotal += $activity[4];
    }
    return $grossTotal;
}

/**
 * @param float $grossTotal
 */
function displayGrossTotal(float $grossTotal)
{
    echo insertSpacePadding("TOTAL BRUT", 32, STR_PAD_RIGHT);
    setlocale(LC_MONETARY, 'ro_RO.UTF-8');
    $formattedGrossTotal = money_format('%.2i', $grossTotal);
    echo insertSpacePadding($formattedGrossTotal, 35, STR_PAD_LEFT) . PHP_EOL . PHP_EOL;
}

/**
 * @param string $contributionsString
 * @param float $grossTotal
 * @return array
 */
function getContributions(string $contributionsString, float $grossTotal): array
{
    $contributions = explode('%', $contributionsString);
    foreach ($contributions as $contribution) {
        $contributionPercentIndex = strcspn($contribution, '0123456789');
        if ($contributionPercentIndex != 0) {
            $contributionPercent = (float)substr(str_replace(',', '.', $contribution),
                $contributionPercentIndex);
            $contributionPercents[] = $contributionPercent;
            $contributionName = substr($contribution, 0, $contributionPercentIndex);
            $contributionName = mb_strtoupper($contributionName);
            $contributionValue = (float)$contributionPercent * $grossTotal / 100;
            $contributionNamesPercentsValues[] = ['key' => $contributionName, 'percent' => $contributionPercent,
                'value' => $contributionValue];
        }


    }
    return $contributionNamesPercentsValues;
}

/**
 * @param array $contribution
 */
function displayContributionLine(array $contribution)
{
    setlocale(LC_MONETARY, 'ro_RO.UTF-8');
    $contributionValue = money_format('%.2i', $contribution['value']);
    $column1 = insertSpacePadding($contribution['key'], 20, STR_PAD_RIGHT);
    $column2 = insertSpacePadding($contribution['percent'] . '%', 34, STR_PAD_LEFT);
    $column3 = insertSpacePadding($contributionValue, 12, STR_PAD_LEFT);
    echo $column1 . $column2 . '|' . $column3 . PHP_EOL;

}

/**
 * @param array $contributions
 */
function displayContributions(array $contributions)
{
    echo insertSpacePadding("Contributii", 67, STR_PAD_RIGHT) . PHP_EOL;
    echo str_pad('', 67, '-', STR_PAD_BOTH) . PHP_EOL;
    foreach ($contributions as $contribution) {
        displayContributionLine($contribution);
    }
    echo str_pad('', 67, '-', STR_PAD_BOTH) . PHP_EOL;

}

/**
 * @param array $contributions
 * @param float $grossTotal
 * @return float
 */
function computeTotal(array $contributions, float $grossTotal): float
{
    $totalContributions = 0;
    foreach ($contributions as $contribution) {
        $totalContributions += $contribution['value'];
    }
    return $grossTotal - $totalContributions;
}

/**
 * @param float $total
 */
function displayTotal(float $total)
{
    echo PHP_EOL . insertSpacePadding("TOTAL", 32, STR_PAD_RIGHT);
    setlocale(LC_MONETARY, 'ro_RO.UTF-8');
    $formattedTotal = money_format('%.2i', $total);
    echo insertSpacePadding($formattedTotal, 35, STR_PAD_LEFT) . PHP_EOL . PHP_EOL;
}

/**
 * @param string $employeeRegistration
 */
function displayLeaflet(string $employeeRegistration) : void
{
    /*TO DO:

    // parse input => internal data structure
    $data = parse($employeeRegistration);

    $processedData = process($data);

    output($processedData);*/


    $registrationParts = splitString($employeeRegistration);
    $name = getFormattedName($registrationParts['firstName'], $registrationParts['lastName']);
    displayPersonalInformation($name, $registrationParts['cnp']);
    $activities = getSortedActivities($registrationParts['activities']);
    displayActivities($activities);
    $grossTotal = computeGrossTotal($activities);
    displayGrossTotal($grossTotal);
    $contributions = getContributions($registrationParts['contributions'], $grossTotal);
    displayContributions($contributions);
    $total = computeTotal($contributions, $grossTotal);
    displayTotal($total);
}

/*TO DO:
function parse($s) {
    $names = parseNames($s);
    $activities = parseActivities($s);

}*/