<?php
    namespace App\Database;

    use App\Database\Connection;
    use App\String\Random;


    class GenerateUniqueId {
        public static function generateUniqueId(int $size, string $table, string $columnName): string {

            $pdo = (new Connection())->getPdo();
            
            $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
            $columnName = preg_replace('/[^a-zA-Z0-9_]/', '', $columnName);

            $sql = "SELECT COUNT(*) FROM `$table` WHERE `$columnName` = :random_id";
            $stmt = $pdo->prepare($sql);

            while (true) {
                $randomId = Random::randomCode($size); 

                $stmt->execute([':random_id' => $randomId]);
                $count = $stmt->fetchColumn();

                if ($count == 0) {
                    return $randomId;
                }
            }
        }
    }
?>