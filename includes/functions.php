<?php
class TaskManager {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function createTask($title, $description, $difficulty_level, $start_date, $duration, $created_by, $assigned_to) {
        try {
            $query = "INSERT INTO tasks (title, description, difficulty_level, start_date, duration, created_by, assigned_to) 
                      VALUES (:title, :description, :difficulty_level, :start_date, :duration, :created_by, :assigned_to)";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":title", $title);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":difficulty_level", $difficulty_level);
            $stmt->bindParam(":start_date", $start_date);
            $stmt->bindParam(":duration", $duration);
            $stmt->bindParam(":created_by", $created_by);
            $stmt->bindParam(":assigned_to", $assigned_to);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function updateTaskStatus($task_id, $status, $user_id) {
        try {
            $query = "UPDATE tasks SET status = :status WHERE id = :task_id AND assigned_to = :user_id";
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":task_id", $task_id);
            $stmt->bindParam(":user_id", $user_id);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function addTaskEvaluation($task_id, $score, $evaluated_by) {
        try {
            $query = "INSERT INTO task_evaluations (task_id, score, evaluated_by) 
                      VALUES (:task_id, :score, :evaluated_by)";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":task_id", $task_id);
            $stmt->bindParam(":score", $score);
            $stmt->bindParam(":evaluated_by", $evaluated_by);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function addComment($task_id, $user_id, $comment) {
        try {
            $query = "INSERT INTO comments (task_id, user_id, comment) 
                      VALUES (:task_id, :user_id, :comment)";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":task_id", $task_id);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":comment", $comment);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }
}