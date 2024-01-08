<?php

namespace Alura\Pdo\Infrastructure\Repository;

use Alura\Pdo\Domain\Model\Phone;
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
      $student = new Student(
        $studentData['id'],
        $studentData['name'],
        new \DateTimeImmutable($studentData['birth_date'])
      );

      $this->fillPhonesOf($student);

      $studentList[] = $student;
    }

    return $studentList;
  }

  private function fillPhonesOf(Student $student): void
  {
    $sql = 'SELECT id, area_code, number FROM phones WHERE student_id = ?;';
    $statement = $this->connection->prepare($sql);
    $statement->bindValue(1, $student->id(), \PDO::PARAM_INT);
    $statement->execute();
    $phonesDataList = $statement->fetchAll();

    foreach ($phonesDataList as $phoneData) {
      $phone = new Phone(
        $phoneData['id'],
        $phoneData['area_code'],
        $phoneData['number']
      );

      $student->addPhone($phone);
    }
  }

  public function studentsWithPhones(): array
  {
    $sqlQuery = 'SELECT students.id,
                        students.name,
                        students.birth_date,
                        phones.id AS phone_id,
                        phones.area_code,
                        phones.number
                   FROM students
                   JOIN phones
                     ON students.id = phones.student_id;';
    $statement = $this->connection->query($sqlQuery);
    $result = $statement->fetchAll();

    $studentList = [];
    foreach ($result as $row) {
      if (!array_key_exists($row['id'], $studentList)) {
        $studentList[$row['id']] = new Student(
          $row['id'],
          $row['name'],
          new \DateTimeImmutable($row['birth_date'])
        );
      }

      $phone = new Phone(
        $row['phone_id'],
        $row['area_code'],
        $row['number']
      );

      $studentList[$row['id']]->addPhone($phone);
    }

    return $studentList;
  }
}