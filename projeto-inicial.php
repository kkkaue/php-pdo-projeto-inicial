<?php

use Alura\Pdo\Domain\Model\Student;

require_once 'vendor/autoload.php';

$student = new Student(
    null,
    'Kauê de Magalhães',
    new \DateTimeImmutable('2001-09-27')
);

echo $student->age();
