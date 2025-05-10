<?php

require_once '../db.php';

class CarModel {
    public function searchCars($params) {
        global $pdo;

        $sql = "SELECT * FROM ads WHERE 1=1";
        $stmtParams = [];

        if (!empty($params['make'])) {
            $sql .= " AND make LIKE :make";
            $stmtParams[':make'] = '%' . $params['make'] . '%';
        }

        if (!empty($params['model_year'])) {
            $sql .= " AND model_year = :model_year";
            $stmtParams[':model_year'] = $params['model_year'];
        }

        if (!empty($params['registration_number'])) {
            if ($params['registration_number'] === 'Unknown') {
                if (!empty($params['registration_number_from_image'])) {
                    $sql .= " AND registration_number_from_image LIKE :registration_number_from_image";
                    $stmtParams[':registration_number_from_image'] = '%' . $params['registration_number_from_image'] . '%';
                }
            } else {
                $sql .= " AND (registration_number LIKE :reg_num 
                              OR registration_number_from_image LIKE :reg_num_img)";
                $stmtParams[':reg_num'] = '%' . $params['registration_number'] . '%';
                $stmtParams[':reg_num_img'] = '%' . $params['registration_number'] . '%';
            }
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($stmtParams);
        
        return $stmt->fetchAll(); 
    }
}
