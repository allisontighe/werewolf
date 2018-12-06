<?php
class Role {
    private $id;
    private $name;
    private $evil;
    private $taskType;
    private $description;
    private $about;
    public function __construct(int $id, string $name, bool $evil, int $taskType, string $description, string $about = '') {
        $this->id = $id;
        $this->name = $name;
        $this->evil = $evil;
        $this->taskType = $taskType;
        $this->description = $description;
        $this->about = $about;
    }
    public function getId(): int {
        return $this->id;
    }
    public function getName(): string {
        return $this->name;
    }
    public function getEvil(): bool {
        return $this->evil;
    }
    public function getTaskType(): int {
        return $this->taskType;
    }
    public function getDescription(): string {
        return $this->description;
    }
}