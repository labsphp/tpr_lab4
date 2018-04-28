<?php
/**
 * Created by PhpStorm.
 * User: Serhii
 * Date: 26.04.2018
 * Time: 0:12
 */

declare(strict_types = 1);
include_once 'VotingMethods.php';
$data = include_once "loadData.php";
$votingMethods = new VotingMethods($data);
$rank = $votingMethods->absoluteMajority();
echo 'Метод абсолютного большинства: ';
var_dump($rank);
echo '<hr>';
echo 'Метод Симпсона';
$rank = $votingMethods->simpsonMethod();
var_dump($rank);
echo '<hr>';
echo 'Метод последовательного исключения';
$rank = $votingMethods->consecutiveExclusion();
var_dump($rank);