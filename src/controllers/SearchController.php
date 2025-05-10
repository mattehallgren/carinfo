<?php

require_once '../models/CarModel.php';

class SearchController {
    public function search($params) {
        $carModel = new CarModel();
        $results = $carModel->searchCars($params);

        return $results;
    }
}
