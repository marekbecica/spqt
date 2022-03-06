<?php

class JobService
{
    const JOB_STATUS_NEW = "NEW";
    const JOB_STATUS_PROCESSING = "PROCESSING";
    const JOB_STATUS_DONE = "DONE";
    const JOB_STATUS_ERROR = "ERROR";

    /**
     * @var PDO
     */
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Return one available job
     * @return array|null
     */
    public function getAvailableJob(): ?array
    {
        $query = $this->pdo->prepare("SELECT * FROM jobs WHERE status=? LIMIT 1");
        $query->execute([self::JOB_STATUS_NEW]);
        $jobRow = $query->fetch(\PDO::FETCH_ASSOC);
        if (empty($jobRow)) {
            return null;
        }
        return $jobRow;
    }

    /**
     * Lock job row with status PROCESSING
     * @param int $id
     * @return bool
     */
    public function setJobStatusProcessing(int $id): bool
    {
        try {
            $query = $this->pdo->prepare("UPDATE jobs SET status=? WHERE id=? and status=?");
            $query->execute([self::JOB_STATUS_PROCESSING, $id, self::JOB_STATUS_NEW]);
            $count = $query->rowCount();
            return $count > 0;
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }

    }

    /**
     *
     * @param int $id
     * @param string $status
     * @param int|null $httpCode
     * @return bool
     */
    public function setJobResult(int $id, string $status, ?int $httpCode): bool
    {
        try {
            $query = $this->pdo->prepare("UPDATE jobs SET status=?, http_code=? WHERE id=? and status=?");
            $query->execute([$status, $httpCode, $id, self::JOB_STATUS_PROCESSING]);
            $count = $query->rowCount();
            return $count > 0;
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
}