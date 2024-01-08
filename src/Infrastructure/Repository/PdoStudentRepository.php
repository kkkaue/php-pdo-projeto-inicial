<?php

namespace Alura\Pdo\Infrastructure\Repository;

use Alura\Pdo\Domain\Model\Student;
use Alura\Pdo\Domain\Repository\StudentRepository;
use PDO;

class PdoStudentRepository implements StudentRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  public function allStudents(): array
  {
    $statement = $this->connection->query('SELECT * FROM students;');
    return $this->hydrateStudentList($statement);
  }

  public function studentsBirthAt(\DateTimeInterface $birthDate): array
  {
    $statement = $this->connection->prepare('SELECT * FROM students WHERE birth_date = ?;');
    $statement->bindValue(1, $birthDate->format('Y-m-d'));
    $statement->execute();
    return $this->hydrateStudentList($statement);
  }

  public function save(Student $student): bool
  {
    if ($student->id() === null) {
      return $this->insert($student);
    }

    return $this->update($student);
  }

  public function remove(Student $student): bool
  {
    $statement = $this->connection->prepare('DELETE FROM students WHERE id = ?;');
    $statement->bindValue(1, $student->id(), \PDO::PARAM_INT);
    return $statement->execute();
  }

  private function insert(Student $student): bool
  {
    $statement = $this->connection->prepare('INSERT INTO students (name, birth_date) VALUES (:name, :birth_date);');
    $statement->bindValue(':name', $student->name());
    $statement->bindValue(':birth_date', $student->birthDate()->format('Y-m-d'));
    return $statement->execute();
  }

  private function update(Student $student): bool
  {
    $statement = $this->connection->prepare('UPDATE students SET name = :name, birth_date = :birth_date WHERE id = :id;');
    $statement->bindValue(':name', $student->name());
    $statement->bindValue(':birth_date', $student->birthDate()->format('Y-m-d'));
    $statement->bindValue(':id', $student->id(), \PDO::PARAM_INT);
    return $statement->execute();
  }

  private function hydrateStudentList(\PDOStatement $statement): array
  {
    $studentDataList = $statement->fetchAll();
    $studentList = [];

    foreach ($studentDataList as $studentData) {
      $studentList[] = new Student(
        $studentData['id'],
        $studentData['name'],
        new \DateTimeImmutable($studentData['birth_date'])
      );
    }

    return $studentList;
  }
}