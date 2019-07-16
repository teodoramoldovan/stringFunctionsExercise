<?php
require_once 'strings.php';
const EMPLOYEE_REGISTRATION = "ana+maria|stanciu|2900312123321|[usoare_2;cules_struguri;120h30m*10.14/h]" .
    ",[usoare_1;curatit_butuci;30h*10.50/h]|cass5.2%somaj0.5%cas15,8%";
setlocale(LC_MONETARY, 'ro_RO.UTF-8');
displayLeaflet(EMPLOYEE_REGISTRATION);